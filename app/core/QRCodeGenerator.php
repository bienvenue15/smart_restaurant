<?php
/**
 * QR Code Generator for Restaurant Tables
 * Uses Google Charts API for QR generation
 */

class QRCodeGenerator {
    
    private $imageDir;
    private $baseUrl;
    
    public function __construct() {
        $this->imageDir = __DIR__ . '/../../images/qrcodes/';
        $this->baseUrl = rtrim(BASE_URL, '/');
        
        // Create base directory if it doesn't exist
        if (!file_exists($this->imageDir)) {
            mkdir($this->imageDir, 0755, true);
        }
    }
    
    /**
     * Generate QR code for a table
     */
    public function generateForTable($tableNumber, $qrCode, $restaurantSlug = null, $restaurantId = null) {
        $directoryKey = $this->getDirectoryKey($restaurantSlug, $restaurantId);
        $targetDir = $this->imageDir . $directoryKey . '/';
        
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        $url = $this->buildMenuUrl($restaurantSlug, $qrCode);
        $filename = $directoryKey . '-table-' . $tableNumber . '.png';
        $filepath = $targetDir . $filename;
        
        // Create QR code using simple matrix generation
        try {
            $this->createQRImage($url, $filepath, [
                'table' => $tableNumber,
                'slug' => $restaurantSlug ?: $directoryKey
            ]);
            
            return [
                'status' => 'OK',
                'filename' => $filename,
                'path' => $filepath,
                'url' => $url,
                'restaurant_directory' => $directoryKey
            ];
        } catch (Exception $e) {
            return [
                'status' => 'FAIL',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create a simple QR code image
     * Using API services or simple placeholder
     */
    private function createQRImage($url, $filepath, $context = []) {
        // Try multiple QR code APIs
        $apis = [
            'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($url),
            'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=' . urlencode($url)
        ];
        
        foreach ($apis as $apiUrl) {
            $imageData = @file_get_contents($apiUrl);
            
            if ($imageData !== false && strlen($imageData) > 100) {
                file_put_contents($filepath, $imageData);
                return true;
            }
        }
        
        // If all APIs fail, create a placeholder image
        $this->createPlaceholderQR($url, $filepath, $context);
        return true;
    }
    
    /**
     * Create a placeholder QR code image with table info
     */
    private function createPlaceholderQR($url, $filepath, $context = []) {
        $width = 300;
        $height = 300;
        
        $image = imagecreate($width, $height);
        
        // Colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $gray = imagecolorallocate($image, 200, 200, 200);
        
        // Fill background
        imagefill($image, 0, 0, $white);
        
        // Draw border
        imagerectangle($image, 0, 0, $width-1, $height-1, $black);
        
        // Extract table number from URL
        preg_match('/qr=([^&]+)/', $url, $qrMatches);
        $qrSnippet = isset($qrMatches[1]) ? substr($qrMatches[1], 0, 10) . '...' : 'N/A';
        $tableNum = $context['table'] ?? 'N/A';
        $slugLabel = strtoupper(str_replace('-', ' ', $context['slug'] ?? 'Restaurant'));
        
        // Add text
        $text1 = $slugLabel;
        $text2 = "Table: $tableNum";
        $text3 = "QR: $qrSnippet";
        
        // Write text (centered)
        imagestring($image, 4, 60, 120, $text1, $black);
        imagestring($image, 5, 80, 150, $text2, $black);
        imagestring($image, 3, 70, 180, $text3, $gray);
        
        // Save image
        imagepng($image, $filepath);
        imagedestroy($image);
    }
    
    /**
     * Generate QR codes for all tables
     */
    public function generateAllTables($db) {
        try {
            $query = "SELECT rt.table_number, rt.qr_code, rt.restaurant_id, r.slug
                      FROM restaurant_tables rt
                      INNER JOIN restaurants r ON rt.restaurant_id = r.id
                      ORDER BY r.slug, rt.table_number";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results = [];
            
            foreach ($tables as $table) {
                $result = $this->generateForTable(
                    $table['table_number'],
                    $table['qr_code'],
                    $table['slug'],
                    $table['restaurant_id']
                );
                $results[] = array_merge($result, [
                    'table' => $table['table_number'],
                    'restaurant_slug' => $table['slug']
                ]);
            }
            
            return [
                'status' => 'OK',
                'results' => $results
            ];
            
        } catch (PDOException $e) {
            return [
                'status' => 'FAIL',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get QR code image path for a table
     */
    public function getQRCodePath($restaurantSlug, $tableNumber, $restaurantId = null) {
        $directoryKey = $this->getDirectoryKey($restaurantSlug, $restaurantId);
        $filename = $directoryKey . '-table-' . $tableNumber . '.png';
        $filepath = $this->imageDir . $directoryKey . '/' . $filename;
        
        if (file_exists($filepath)) {
            return '/restaurant/images/qrcodes/' . $directoryKey . '/' . $filename;
        }
        
        return null;
    }
    
    /**
     * Build tenant-aware menu URL
     */
    private function buildMenuUrl($restaurantSlug, $qrCode) {
        $encodedSlug = $restaurantSlug ? rawurlencode($restaurantSlug) : null;
        $path = $encodedSlug ? $this->baseUrl . '/' . $encodedSlug : $this->baseUrl;
        
        $params = [
            'req' => 'menu',
            'qr' => $qrCode
        ];
        
        if ($encodedSlug) {
            $params['tenant'] = $restaurantSlug;
        }
        
        return $path . '/?' . http_build_query($params);
    }
    
    /**
     * Determine directory key for restaurant assets
     */
    private function getDirectoryKey($restaurantSlug, $restaurantId) {
        if (!empty($restaurantSlug)) {
            return preg_replace('/[^a-z0-9\-]/', '-', strtolower($restaurantSlug));
        }
        
        if (!empty($restaurantId)) {
            return 'restaurant-' . $restaurantId;
        }
        
        return 'global';
    }
}
?>
