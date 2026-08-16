<?php
/**
 * API Endpoint: Get Translations
 * Returns translations for a specific language as JSON
 */

// Allow CORS for local development
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Get language from query parameter
$lang = $_GET['lang'] ?? 'en';

// Validate language
$allowedLangs = ['en', 'fr', 'rw', 'sw'];
if (!in_array($lang, $allowedLangs)) {
    $lang = 'en';
}

// Load translation file
$translationFile = __DIR__ . '/../src/languages/' . $lang . '.php';

if (file_exists($translationFile)) {
    $translations = include $translationFile;
    echo json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    // Return empty object if file not found
    echo json_encode([], JSON_UNESCAPED_UNICODE);
}
