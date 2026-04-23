#!/usr/bin/env python3
from __future__ import annotations

import argparse
import datetime as dt
import json
import random
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request


ROLE_CYCLE = ("miembro", "docente", "bibliotecario")


def build_ip_pool(size: int) -> list[str]:
    # Rango de documentacion TEST-NET-3 (RFC 5737), util para demo.
    # Se generan IPs unicas para simular visitantes distintos.
    ips: list[str] = []
    base_a, base_b, base_c = 203, 0, 113
    host = 1
    while len(ips) < size:
        ips.append(f"{base_a}.{base_b}.{base_c}.{host}")
        host += 1
        if host > 254:
            host = 1
            base_b = (base_b + 1) % 255
    return ips


def build_user_agent(index: int) -> str:
    families = [
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
        "Mozilla/5.0 (X11; Linux x86_64)",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 13_5)",
        "Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)",
        "Mozilla/5.0 (Android 14; Mobile)",
    ]
    engines = [
        "AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36",
        "AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15",
        "Gecko/20100101 Firefox/126.0",
    ]
    fam = random.choice(families)
    eng = random.choice(engines)
    return f"{fam} {eng} DemoVisitBot/{index}"


def role_and_user(index: int) -> tuple[str, str]:
    role = ROLE_CYCLE[(index - 1) % len(ROLE_CYCLE)]
    user = f"demo-{role}-{index:04d}"
    return role, user


def build_distinct_timestamps(count: int, days_span: int) -> list[str]:
    now = dt.datetime.now().replace(microsecond=0)
    span_seconds = max(1, days_span * 24 * 60 * 60)

    timestamps: list[dt.datetime] = []
    for i in range(count):
        offset = int((i * span_seconds) / max(1, count))
        ts = now - dt.timedelta(seconds=offset)
        timestamps.append(ts)

    timestamps.reverse()
    return [t.strftime("%Y-%m-%d %H:%M:%S") for t in timestamps]


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Generar visitas demo por HTTP")
    parser.add_argument("--url", default="https://biblioteca.istel.edu.ec/", help="URL objetivo")
    parser.add_argument("--count", type=int, default=200, help="Cantidad de visitas a generar")
    parser.add_argument(
        "--mode",
        choices=("db", "http"),
        default="db",
        help="db=inserta en visits_log con fechas distintas, http=hace GET al sitio",
    )
    parser.add_argument(
        "--days-span",
        type=int,
        default=30,
        help="Rango de dias para distribuir created_at distintos",
    )
    parser.add_argument("--timeout", type=float, default=8.0, help="Timeout por solicitud (segundos)")
    parser.add_argument("--min-sleep", type=float, default=0.02, help="Espera minima entre requests")
    parser.add_argument("--max-sleep", type=float, default=0.12, help="Espera maxima entre requests")
    return parser.parse_args()


def write_visits_direct_db(url: str, count: int, ips: list[str], timestamps: list[str]) -> tuple[int, int]:
    referer = "https://istel.edu.ec/"
    payload: list[dict[str, str]] = []

    parsed = urllib.parse.urlparse(url)
    page = parsed.path or "/"

    for i in range(count):
        index = i + 1
        role, user = role_and_user(index)
        ua = f"{build_user_agent(index)} role={role}; user={user}"

        payload.append(
            {
                "page": page,
                "ip": ips[i],
                "user_agent": ua,
                "referer": referer,
                "created_at": timestamps[i],
            }
        )

    php_code = r'''define("BASE_PATH", getcwd());
require "bootstrap.php";
$pdo = Core\Database::connect();
$json = stream_get_contents(STDIN);
$rows = json_decode($json, true);
if (!is_array($rows)) { fwrite(STDERR, "JSON payload invalido\n"); exit(2); }
$stmt = $pdo->prepare("INSERT INTO visits_log (user_id, branch_id, page, ip_address, user_agent, referer, created_at) VALUES (NULL, NULL, ?, ?, ?, ?, ?)");
$ok = 0;
$fail = 0;
foreach ($rows as $r) {
    try {
        $stmt->execute([
            (string)($r['page'] ?? '/'),
            (string)($r['ip'] ?? ''),
            (string)($r['user_agent'] ?? ''),
            (string)($r['referer'] ?? ''),
            (string)($r['created_at'] ?? date('Y-m-d H:i:s')),
        ]);
        $ok++;
    } catch (Throwable) {
        $fail++;
    }
}
echo json_encode(["ok" => $ok, "fail" => $fail], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
'''

    proc = subprocess.run(
        ["php", "-r", php_code],
        input=json.dumps(payload, ensure_ascii=False).encode("utf-8"),
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        check=False,
        cwd="/var/www/html",
    )

    if proc.returncode != 0:
        err = proc.stderr.decode("utf-8", errors="replace").strip() or "Error ejecutando PHP"
        print(f"Error en modo db: {err}", file=sys.stderr)
        return 0, count

    out = proc.stdout.decode("utf-8", errors="replace").strip()
    try:
        result = json.loads(out)
        return int(result.get("ok", 0)), int(result.get("fail", count))
    except json.JSONDecodeError:
        print(f"Respuesta inesperada de PHP: {out}", file=sys.stderr)
        return 0, count


def send_visits_http(url: str, count: int, timeout: float, min_sleep: float, max_sleep: float, ips: list[str], timestamps: list[str]) -> tuple[int, int]:
    ok = 0
    failed = 0

    for i in range(count):
        index = i + 1
        ip = ips[i]
        role, user = role_and_user(index)
        ua = f"{build_user_agent(index)} role={role}; user={user}"

        req = urllib.request.Request(url, method="GET")
        req.add_header("User-Agent", ua)
        req.add_header("X-Forwarded-For", ip)
        req.add_header("X-Real-IP", ip)
        req.add_header("X-Demo-Visit-At", timestamps[i])
        req.add_header("X-Demo-Role", role)
        req.add_header("X-Demo-User", user)
        req.add_header("Referer", "https://istel.edu.ec/")
        req.add_header("Accept", "text/html,application/xhtml+xml")

        try:
            with urllib.request.urlopen(req, timeout=timeout) as resp:
                status = getattr(resp, "status", 200)
                if 200 <= status < 400:
                    ok += 1
                else:
                    failed += 1
        except (urllib.error.URLError, urllib.error.HTTPError, TimeoutError):
            failed += 1

        if (i + 1) % 25 == 0 or (i + 1) == count:
            print(f"Progreso: {i + 1}/{count} | OK={ok} | FAIL={failed}")

        if i + 1 < count:
            time.sleep(random.uniform(min_sleep, max_sleep))

    return ok, failed


def main() -> int:
    args = parse_args()

    if args.count <= 0:
        print("--count debe ser mayor a 0", file=sys.stderr)
        return 2

    if args.min_sleep < 0 or args.max_sleep < 0 or args.max_sleep < args.min_sleep:
        print("Rango de espera invalido", file=sys.stderr)
        return 2

    if args.days_span < 1:
        print("--days-span debe ser >= 1", file=sys.stderr)
        return 2

    target = args.url.strip()
    if not (target.startswith("http://") or target.startswith("https://")):
        print("La URL debe iniciar con http:// o https://", file=sys.stderr)
        return 2

    ips = build_ip_pool(args.count)
    timestamps = build_distinct_timestamps(args.count, args.days_span)

    print(f"Iniciando generacion demo: {args.count} visitas -> {target} (modo={args.mode})")
    print(
        f"Fechas distribuidas entre {timestamps[0]} y {timestamps[-1]} | roles: miembro/docente/bibliotecario"
    )

    if args.mode == "db":
        ok, failed = write_visits_direct_db(target, args.count, ips, timestamps)
        print(f"Progreso: {args.count}/{args.count} | OK={ok} | FAIL={failed}")
    else:
        ok, failed = send_visits_http(
            target,
            args.count,
            args.timeout,
            args.min_sleep,
            args.max_sleep,
            ips,
            timestamps,
        )

    print("Finalizado")
    print(f"Resumen -> solicitadas={args.count}, exitosas={ok}, fallidas={failed}")

    return 0 if ok > 0 else 1


if __name__ == "__main__":
    raise SystemExit(main())
