<?php
/**
 * Language Switcher Component Helper
 * Include this in your views to easily render the language switcher
 */

/**
 * Render the language switcher dropdown
 * 
 * @param string $position 'header' | 'sidebar' | 'footer' | 'inline'
 * @param array $options Additional CSS classes or styles
 * @return string HTML for language switcher
 */
function renderLanguageSwitcher($position = 'header', $options = []) {
    $baseClass = 'language-switcher';
    $positionClass = 'lang-switcher-' . $position;
    $customClass = $options['class'] ?? '';
    $customStyle = $options['style'] ?? '';
    
    $languages = Language::getAvailableLanguages();
    $currentLang = Language::getCurrentLanguage();
    $current = $languages[$currentLang] ?? $languages['en'];
    
    ob_start();
    ?>
    <div class="<?php echo $baseClass . ' ' . $positionClass . ' ' . $customClass; ?>" 
         <?php echo $customStyle ? 'style="' . $customStyle . '"' : ''; ?>>
        <div class="language-current" onclick="dynamicLangSwitcher.toggleDropdown()">
            <span class="language-flag"><?php echo $current['flag']; ?></span>
            <span class="language-name"><?php echo $current['native']; ?></span>
            <i class="fas fa-chevron-down"></i>
        </div>
        <div class="language-dropdown">
            <?php foreach ($languages as $code => $lang): ?>
                <a href="javascript:void(0)" 
                   onclick="dynamicLangSwitcher.setLanguage('<?php echo $code; ?>')"
                   data-lang="<?php echo $code; ?>"
                   class="language-option <?php echo $code === $currentLang ? 'active' : ''; ?>">
                    <span class="language-option-flag"><?php echo $lang['flag']; ?></span>
                    <div class="language-option-text">
                        <div class="language-option-native"><?php echo $lang['native']; ?></div>
                        <div class="language-option-name"><?php echo $lang['name']; ?></div>
                    </div>
                    <?php if ($code === $currentLang): ?>
                        <i class="fas fa-check"></i>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Get current language info
 * 
 * @return array ['code' => 'en', 'name' => 'English', 'native' => 'English', 'flag' => '🇬🇧']
 */
function getCurrentLanguageInfo() {
    $languages = Language::getAvailableLanguages();
    $currentLang = Language::getCurrentLanguage();
    
    return array_merge(
        ['code' => $currentLang],
        $languages[$currentLang] ?? $languages['en']
    );
}

/**
 * Get all available languages
 * 
 * @return array Array of language info
 */
function getAllLanguages() {
    return Language::getAvailableLanguages();
}

/**
 * Translate and echo (shorthand)
 * 
 * @param string $key Translation key
 * @param array $replacements Placeholder replacements
 */
function _e($key, $replacements = []) {
    echo Language::translate($key, $replacements);
}

/**
 * Translate with fallback
 * 
 * @param string $key Translation key
 * @param string $fallback Default text if translation not found
 * @param array $replacements Placeholder replacements
 * @return string Translated text or fallback
 */
function __f($key, $fallback, $replacements = []) {
    $translation = Language::translate($key, $replacements);
    return ($translation === $key) ? $fallback : $translation;
}

/**
 * Check if a translation key exists
 * 
 * @param string $key Translation key
 * @return bool
 */
function translationExists($key) {
    $translation = Language::translate($key);
    return $translation !== $key;
}

/**
 * Get language-specific asset
 * Useful for language-specific images, PDFs, etc.
 * 
 * @param string $assetPath Base asset path
 * @param string $fallbackLang Fallback language if current not found
 * @return string Full asset path with language code
 */
function getLanguageAsset($assetPath, $fallbackLang = 'en') {
    $currentLang = Language::getCurrentLanguage();
    $langAsset = str_replace('{lang}', $currentLang, $assetPath);
    
    // Check if file exists (for local files)
    if (file_exists($langAsset)) {
        return $langAsset;
    }
    
    // Fallback to default language
    return str_replace('{lang}', $fallbackLang, $assetPath);
}

/**
 * Generate language switcher links (simple text links)
 * 
 * @param string $separator Separator between links
 * @param bool $showFlags Include flag emojis
 * @return string HTML links
 */
function getLanguageLinks($separator = ' | ', $showFlags = true) {
    $languages = Language::getAvailableLanguages();
    $currentLang = Language::getCurrentLanguage();
    $links = [];
    
    foreach ($languages as $code => $lang) {
        $flag = $showFlags ? $lang['flag'] . ' ' : '';
        $active = ($code === $currentLang) ? 'class="active"' : '';
        $links[] = '<a href="?lang=' . $code . '" ' . $active . '>' . $flag . $lang['native'] . '</a>';
    }
    
    return implode($separator, $links);
}

/**
 * Include language-specific partial view
 * 
 * @param string $partialPath Path to partial view file
 * @param array $data Data to pass to partial
 */
function includeLanguagePartial($partialPath, $data = []) {
    $currentLang = Language::getCurrentLanguage();
    
    // Try language-specific file first
    $langFile = str_replace('.php', '_' . $currentLang . '.php', $partialPath);
    
    if (file_exists($langFile)) {
        extract($data);
        include $langFile;
    } elseif (file_exists($partialPath)) {
        // Fallback to default
        extract($data);
        include $partialPath;
    }
}

/**
 * Get HTML lang attribute value
 * 
 * @return string Language code for HTML lang attribute
 */
function getHtmlLang() {
    $langMap = [
        'en' => 'en',
        'fr' => 'fr',
        'rw' => 'rw',
        'sw' => 'sw'
    ];
    
    $currentLang = Language::getCurrentLanguage();
    return $langMap[$currentLang] ?? 'en';
}

/**
 * Format price with currency symbol based on language
 * 
 * @param float $amount Amount to format
 * @param string $currency Currency code (default: RWF)
 * @return string Formatted price
 */
function formatPrice($amount, $currency = 'RWF') {
    $currentLang = Language::getCurrentLanguage();
    
    // Currency symbol position varies by language
    $formatted = Language::formatNumber($amount, 2);
    
    switch ($currentLang) {
        case 'fr':
            return $formatted . ' ' . $currency;
        case 'en':
        case 'rw':
        case 'sw':
        default:
            return $currency . ' ' . $formatted;
    }
}

/**
 * Get text direction for language (for future RTL support)
 * 
 * @return string 'ltr' or 'rtl'
 */
function getTextDirection() {
    // All current languages are LTR
    // Add RTL languages here if needed (Arabic, Hebrew, etc.)
    return 'ltr';
}

/**
 * Create multilingual URL
 * Preserves ALL current URL parameters plus language
 * 
 * @param string $path URL path (optional, defaults to current URL)
 * @param array $params Additional parameters to add/override
 * @return string Complete URL with language parameter and all existing params
 */
function langUrl($path = null, $params = []) {
    $currentLang = Language::getCurrentLanguage();
    
    // If no path provided, use current URL
    if ($path === null) {
        $path = $_SERVER['REQUEST_URI'] ?? '';
    }
    
    // Parse existing URL
    $urlParts = parse_url($path);
    $existingParams = [];
    
    // Get existing query parameters
    if (isset($urlParts['query'])) {
        parse_str($urlParts['query'], $existingParams);
    }
    
    // Merge: existing params + new params + language
    $allParams = array_merge($existingParams, $params, ['lang' => $currentLang]);
    
    // Build final URL
    $basePath = $urlParts['path'] ?? $path;
    $queryString = http_build_query($allParams);
    
    return $basePath . ($queryString ? '?' . $queryString : '');
}
