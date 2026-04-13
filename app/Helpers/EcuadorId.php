<?php
// app/Helpers/EcuadorId.php
declare(strict_types=1);

namespace Helpers;

final class EcuadorId
{
    public static function normalizeCedula(string $value): string
    {
        return preg_replace('/\D+/', '', trim($value)) ?? '';
    }

    public static function isValidCedula(string $value): bool
    {
        $cedula = self::normalizeCedula($value);

        if (!preg_match('/^\d{10}$/', $cedula)) {
            return false;
        }

        $province = (int) substr($cedula, 0, 2);
        $thirdDigit = (int) $cedula[2];

        // Provincias 01..24, 30 para extranjeros registrados; tercer dígito < 6 para persona natural
        if (!(($province >= 1 && $province <= 24) || $province === 30) || $thirdDigit >= 6) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $digit = (int) $cedula[$i];
            if ($i % 2 === 0) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }

        $verifier = (10 - ($sum % 10)) % 10;

        return $verifier === (int) $cedula[9];
    }
}
