<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Return the phone number and its Brazilian variant (with/without the 9th digit).
     *
     * @return string[]
     */
    public static function variants(string $phoneNumber): array
    {
        $variants = [$phoneNumber];
        $alternative = self::brazilianVariant($phoneNumber);

        if ($alternative) {
            $variants[] = $alternative;
        }

        return $variants;
    }

    /**
     * Generate an alternative Brazilian phone number variant by adding or removing the 9th digit.
     */
    public static function brazilianVariant(string $phoneNumber): ?string
    {
        if (! str_starts_with($phoneNumber, '55')) {
            return null;
        }

        $national = substr($phoneNumber, 2);

        // 13 digits total (11 national) = has the 9th digit → remove it
        if (strlen($phoneNumber) === 13 && $national[2] === '9') {
            return '55'.substr($national, 0, 2).substr($national, 3);
        }

        // 12 digits total (10 national) = missing the 9th digit → add it
        if (strlen($phoneNumber) === 12) {
            return '55'.substr($national, 0, 2).'9'.substr($national, 2);
        }

        return null;
    }
}
