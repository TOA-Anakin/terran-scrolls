<?php

namespace App\Helpers;

class StringHelper
{
    /**
     * Clean and format a string as slug.
     * Removes unwanted characters and ensures the string is URL-friendly.
     *
     * @param string $string
     * @return string
     */
    public static function sanitizeForSlug($string)
    {
        $string = str_replace(' ', '-', $string);
        $string = preg_match("/[a-z]/i", $string) ? $string : 'untitled';
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);

        return preg_replace('/-+/', '-', $string);
    }


    /**
     * Sanitizes a string for safe display in HTML, preventing XSS attacks.
     * Converts special characters to HTML entities.
     *
     * @param string $string
     * @return string
     */
    public static function sanitizeForHtml($string)
    {
        $string = str_replace(' ', '-', $string);
        $string = filter_var($string, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        return preg_replace('/-+/', '-', $string);
    }
}
