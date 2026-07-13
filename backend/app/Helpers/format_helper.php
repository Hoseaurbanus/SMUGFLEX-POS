<?php

if (!function_exists('format_currency')) {
    function format_currency(float $amount): string
    {
        $symbol = '₦';
        return $symbol . number_format($amount, 2, '.', ',');
    }
}

if (!function_exists('generate_reference')) {
    function generate_reference(string $prefix): string
    {
        $date = date('Ymd');
        $random = strtoupper(bin2hex(random_bytes(3)));
        return "{$prefix}-{$date}-{$random}";
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        return strtolower($text);
    }
}
