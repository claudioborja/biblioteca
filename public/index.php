<?php
// public/index.php — Único punto de entrada
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('START_TIME', microtime(true));

// Base URL para generar links (ej: /biblioteca o vacío si está en root)
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if (str_ends_with($baseUrl, '/public')) {
    $baseUrl = substr($baseUrl, 0, -7);
}
define('BASE_URL', $baseUrl);

require BASE_PATH . '/bootstrap.php';

$app = new \Core\App();
$app->run();
