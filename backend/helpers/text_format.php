<?php

function format_name_or_address(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $normalized = preg_replace('/\s+/', ' ', trim($value));
    if ($normalized === '') {
        return '';
    }

    return ucwords(strtolower($normalized), " \t\r\n\f\v-'");
}
