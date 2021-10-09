<?php

namespace App\Utils;

use Mcamara\LaravelLocalization\LaravelLocalization;

class CurrentLanguage
{
    private static $language;

    public static function get(): string
    {
        if (self::$language == null) {
            $localization = new LaravelLocalization;
            $supported_languages = $localization->getSupportedLocales();
            if (isset($_GET['lang']) ? isset($supported_languages[$_GET['lang']]) : false) {
                $language = $_GET['lang'];
            } else {
                $language = $localization->getCurrentLocale();
                $url = substr(url()->current(), mb_strlen(url('/') . '/'));
                $current_page = substr($url, 0, strpos($url, '/'));
                if (auth()->check() && !isset($supported_languages[$current_page])) {
                    if (auth()->user()->language != null) {
                        $language = auth()->user()->language->short;
                    }
                }
            }
            app()->setLocale($language);
            self::$language = $language;
        }
        return self::$language;
    }
}
