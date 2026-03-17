<?php

namespace App\Helpers;

class EmailMasker
{
    public static function mask(string $email): string
    {
        return mask_email($email);
    }
}
