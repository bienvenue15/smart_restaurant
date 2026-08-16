<?php
/**
 * Language Management System
 * Supports: English, French, Kinyarwanda, Swahili
 */

class Language {
    private static $currentLang = 'en';
    private static $translations = [];
    private static $availableLanguages = [
        'en' => ['name' => 'English', 'flag' => '🇬🇧', 'native' => 'English'],
        'fr' => ['name' => 'French', 'flag' => '🇫🇷', 'native' => 'Français'],
        'rw' => ['name' => 'Kinyarwanda', 'flag' => '🇷🇼', 'native' => 'Ikinyarwanda'],
        'sw' => ['name' => 'Swahili', 'flag' => '🇹🇿', 'native' => 'Kiswahili']
    ];

    /**
     * Initialize language system
     */
    public static function init() {
        // Check for language in URL parameter
        if (isset($_GET['lang']) && isset(self::$availableLanguages[$_GET['lang']])) {
            self::setLanguage($_GET['lang']);
        }
        // Check session
        elseif (isset($_SESSION['language'])) {
            self::$currentLang = $_SESSION['language'];
        }
        // Check cookie
        elseif (isset($_COOKIE['language'])) {
            self::$currentLang = $_COOKIE['language'];
        }
        // Check browser language
        else {
            self::detectBrowserLanguage();
        }

        // Load translation file
        self::loadTranslations();
    }

    /**
     * Set current language
     */
    public static function setLanguage($lang) {
        if (!isset(self::$availableLanguages[$lang])) {
            $lang = 'en';
        }

        self::$currentLang = $lang;
        $_SESSION['language'] = $lang;
        setcookie('language', $lang, time() + (365 * 24 * 60 * 60), '/'); // 1 year
        
        self::loadTranslations();
    }

    /**
     * Get current language
     */
    public static function getCurrentLanguage() {
        return self::$currentLang;
    }

    /**
     * Get available languages
     */
    public static function getAvailableLanguages() {
        return self::$availableLanguages;
    }

    /**
     * Detect browser language
     */
    private static function detectBrowserLanguage() {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            $browserLang = substr($langs[0], 0, 2);
            
            // Map browser codes to our language codes
            $langMap = [
                'en' => 'en',
                'fr' => 'fr',
                'rw' => 'rw',
                'sw' => 'sw'
            ];
            
            if (isset($langMap[$browserLang])) {
                self::$currentLang = $langMap[$browserLang];
            }
        }
    }

    /**
     * Load translations for current language
     */
    private static function loadTranslations() {
        $file = __DIR__ . '/languages/' . self::$currentLang . '.php';
        
        if (file_exists($file)) {
            self::$translations = include $file;
        } else {
            // Fallback to English
            $file = __DIR__ . '/languages/en.php';
            if (file_exists($file)) {
                self::$translations = include $file;
            }
        }
    }

    /**
     * Translate a key
     * Usage: Language::t('welcome') or __('welcome')
     */
    public static function translate($key, $replacements = []) {
        // Split nested keys (e.g., 'menu.items.add')
        $keys = explode('.', $key);
        $value = self::$translations;

        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                // Return key if translation not found
                return $key;
            }
        }

        // Handle replacements (e.g., 'Hello :name' with [':name' => 'John'])
        if (!empty($replacements) && is_string($value)) {
            foreach ($replacements as $search => $replace) {
                $value = str_replace($search, $replace, $value);
            }
        }

        return $value;
    }

    /**
     * Short alias for translate
     */
    public static function t($key, $replacements = []) {
        return self::translate($key, $replacements);
    }

    /**
     * Format number according to language
     */
    public static function formatNumber($number, $decimals = 0) {
        $locales = [
            'en' => ['decimal' => '.', 'thousands' => ','],
            'fr' => ['decimal' => ',', 'thousands' => ' '],
            'rw' => ['decimal' => ',', 'thousands' => ' '],
            'sw' => ['decimal' => '.', 'thousands' => ',']
        ];

        $format = $locales[self::$currentLang] ?? $locales['en'];
        return number_format($number, $decimals, $format['decimal'], $format['thousands']);
    }

    /**
     * Format currency
     */
    public static function formatCurrency($amount) {
        return 'RWF ' . self::formatNumber($amount, 2);
    }

    /**
     * Format date according to language
     */
    public static function formatDate($date, $format = 'medium') {
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        
        $formats = [
            'en' => [
                'short' => 'm/d/Y',
                'medium' => 'M d, Y',
                'long' => 'F d, Y g:i A',
                'full' => 'l, F d, Y g:i A'
            ],
            'fr' => [
                'short' => 'd/m/Y',
                'medium' => 'd M Y',
                'long' => 'd F Y H:i',
                'full' => 'l d F Y H:i'
            ],
            'rw' => [
                'short' => 'd/m/Y',
                'medium' => 'd M Y',
                'long' => 'd F Y H:i',
                'full' => 'l d F Y H:i'
            ],
            'sw' => [
                'short' => 'd/m/Y',
                'medium' => 'd M Y',
                'long' => 'd F Y H:i',
                'full' => 'l d F Y H:i'
            ]
        ];

        $dateFormat = $formats[self::$currentLang][$format] ?? $formats['en'][$format];
        return date($dateFormat, $timestamp);
    }
}

/**
 * Global helper function for translations
 */
function __($key, $replacements = []) {
    return Language::translate($key, $replacements);
}

/**
 * Short alias
 */
function t($key, $replacements = []) {
    return Language::translate($key, $replacements);
}
