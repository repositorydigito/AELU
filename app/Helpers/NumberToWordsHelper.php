<?php

namespace App\Helpers;

class NumberToWordsHelper
{
    private static $units = [
        '', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve',
        'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve',
    ];

    private static $tens = [
        '', '', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa',
    ];

    private static $hundreds = [
        '', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos',
    ];

    public static function convert($amount)
    {
        $parts = explode('.', number_format($amount, 2, '.', ''));
        $integerPart = (int) $parts[0];
        $decimalPart = $parts[1];

        $words = self::convertInteger($integerPart);

        if ($integerPart == 1) {
            $words .= ' sol';
        } else {
            $words .= ' soles';
        }

        if ($decimalPart != '00') {
            $words .= ' con '.$decimalPart.'/100';
        } else {
            $words .= ' con 00/100';
        }

        return strtoupper($words);
    }

    private static function convertInteger($number)
    {
        if ($number == 0) {
            return 'cero';
        }

        if ($number < 0) {
            return 'menos '.self::convertInteger(abs($number));
        }

        if ($number < 20) {
            return self::$units[$number];
        }

        if ($number < 100) {
            $ten = floor($number / 10);
            $unit = $number % 10;

            if ($ten == 2 && $unit > 0) {
                return 'veinti'.self::$units[$unit];
            }

            return self::$tens[$ten].($unit > 0 ? ' y '.self::$units[$unit] : '');
        }

        if ($number < 1000) {
            $hundred = floor($number / 100);
            $remainder = $number % 100;

            $result = '';
            if ($hundred == 1 && $remainder == 0) {
                $result = 'cien';
            } else {
                $result = self::$hundreds[$hundred];
                if ($remainder > 0) {
                    $result .= ' '.self::convertInteger($remainder);
                }
            }

            return $result;
        }

        if ($number < 1000000) {
            $thousand = floor($number / 1000);
            $remainder = $number % 1000;

            $result = '';
            if ($thousand == 1) {
                $result = 'mil';
            } else {
                $result = self::convertInteger($thousand).' mil';
            }

            if ($remainder > 0) {
                $result .= ' '.self::convertInteger($remainder);
            }

            return $result;
        }

        // Para números mayores a 1 millón (básico)
        if ($number < 1000000000) {
            $million = floor($number / 1000000);
            $remainder = $number % 1000000;

            $result = '';
            if ($million == 1) {
                $result = 'un millón';
            } else {
                $result = self::convertInteger($million).' millones';
            }

            if ($remainder > 0) {
                $result .= ' '.self::convertInteger($remainder);
            }

            return $result;
        }

        return 'número demasiado grande';
    }
}
