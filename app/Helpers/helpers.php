<?php

if (! function_exists('mask_email')) {
    function mask_email(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($name === '' || $domain === '') {
            return $email;
        }

        $visible = substr($name, 0, min(2, strlen($name)));
        return $visible.str_repeat('*', max(3, strlen($name) - 2)).'@'.$domain;
    }
}
