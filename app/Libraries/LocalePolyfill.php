<?php

/**
 * Minimal Locale polyfill for environments without the intl extension.
 * Only implements the methods actually used by CI4 core.
 */
if (! extension_loaded('intl') && ! class_exists('Locale', false)) {
    class Locale
    {
        private static string $default = 'en';

        public static function getDefault(): string
        {
            return self::$default;
        }

        public static function setDefault(string $locale): bool
        {
            self::$default = $locale;
            return true;
        }

        public static function acceptFromHttp(string $header): string|false
        {
            // Return first locale from Accept-Language header
            if (preg_match('/^([a-z]{2,3})(?:[_-][a-z]{2,4})?/i', $header, $m)) {
                return strtolower($m[1]);
            }
            return false;
        }

        public static function getPrimaryLanguage(string $locale): string
        {
            return strtolower(preg_replace('/[_-].*$/', '', $locale));
        }
    }
}
