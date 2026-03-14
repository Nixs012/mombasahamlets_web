<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/admin_auth_helper.php';

// Enforce admin authentication for POST, PUT, DELETE
require_admin_auth();

header('Content-Type: application/json; charset=UTF-8');

// Ensure destination exists
$uploadsDir = realpath(__DIR__ . '/../../frontend/images/uploads');
if (!$uploadsDir) {
    $uploadsDir = __DIR__ . '/../../frontend/images/uploads';
    if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
    $uploadsDir = realpath($uploadsDir);
}

// Helper to convert php.ini shorthand (e.g. 8M) to bytes
function returnBytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $num = (int)$val;
    switch($last) {
        case 'g':
            $num *= 1024;
        case 'm':
            $num *= 1024;
        case 'k':
            $num *= 1024;
    }
    return $num;
}

$postMax = returnBytes(ini_get('post_max_size'));
$uploadMax = returnBytes(ini_get('upload_max_filesize'));
$serverLimit = min($postMax, $uploadMax);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(['server_limit_bytes' => $serverLimit]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!empty($data['clear_all']) && $data['clear_all'] === true) {
        $files = glob($uploadsDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) unlink($file);
        }
        echo json_encode(['message' => 'All images cleared successfully.']);
        exit;
    }

    if (empty($data['path'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Image path not provided.']);
        exit;
    }

    $relativePath = $data['path'];

    // --- Security Validation ---
    // Prevent directory traversal attacks
    if (strpos($relativePath, '..') !== false) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid image path.']);
        exit;
    }

    // Normalize the path - remove leading slashes and ensure consistent separators
    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
    
    // Construct the full path - paths from list-images.php are relative to frontend/
    $projectRoot = realpath(__DIR__ . '/../..');
    $frontendBase = realpath($projectRoot . DIRECTORY_SEPARATOR . 'frontend');
    
    if (!$frontendBase) {
        http_response_code(500);
        echo json_encode(['error' => 'Frontend directory not found.']);
        exit;
    }
    
    // Build the full path relative to frontend directory
    $fullPath = realpath($frontendBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
    $uploadsDir = realpath($frontendBase . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'uploads');

    if (!$uploadsDir) {
        http_response_code(500);
        echo json_encode(['error' => 'Uploads directory not found.']);
        exit;
    }

    // Ensure the file is within the uploads directory (security check)
    if (!$fullPath || strpos($fullPath, $uploadsDir) !== 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden: Cannot delete files outside of the uploads directory.']);
        exit;
    }

    if (file_exists($fullPath)) {
        unlink($fullPath); // Delete the main image

        // Also delete variants (_md, _thumb)
        $pathInfo = pathinfo($fullPath);
        $baseName = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $pathInfo['filename'];
        @unlink($baseName . '_md.' . $pathInfo['extension']);
        @unlink($baseName . '_thumb.' . $pathInfo['extension']);

        echo json_encode(['message' => 'Image deleted successfully.']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Image not found on server.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}
// If $_FILES is empty it can be because POST body exceeded post_max_size
if (empty($_FILES)) {
    $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;
    if ($contentLength > $postMax) {
        http_response_code(413);
        echo json_encode(['error' => 'POST body too large. Increase post_max_size in php.ini']);
        exit;
    }
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

if (!isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Form field "image" not found. Use field name "image" in the upload form.']);
    exit;
}

$file = $_FILES['image'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    $msg = 'Upload error';
    switch ($file['error']) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $msg = 'Uploaded file is too large';
            break;
        case UPLOAD_ERR_PARTIAL:
            $msg = 'File was only partially uploaded';
            break;
        case UPLOAD_ERR_NO_FILE:
            $msg = 'No file was uploaded';
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $msg = 'Missing temporary folder on server';
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $msg = 'Failed to write file to disk';
            break;
        case UPLOAD_ERR_EXTENSION:
            $msg = 'A PHP extension stopped the file upload';
            break;
    }
    http_response_code(400);
    echo json_encode(['error' => $msg, 'code' => $file['error']]);
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed_mimes = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'
];

if (!in_array($mime, $allowed_mimes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Only images and videos allowed. Type detected: ' . $mime]);
    exit;
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$name = uniqid('img_') . '.' . $ext;
$dest = $uploadsDir . DIRECTORY_SEPARATOR . $name;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to move uploaded file']);
    exit;
}

// The web root for the project is 'mombasahamlets_web'. We want paths relative to that.
$projectRoot = realpath(__DIR__ . '/../..');
$rel = str_replace('\\', '/', substr($dest, strlen($projectRoot) + 1));

// Attempt to create resized variants (medium and thumbnail)
function create_resized_variant($src, $dst, $maxW, $maxH) {
    // Check if GD library is available before trying to use image functions
    if (!extension_loaded('gd') || !function_exists('gd_info')) {
        // Silently fail if GD is not available. The original image is already saved.
        return false;
    }

    $info = @getimagesize($src);
    if (!$info) return false;
    list($width, $height) = $info;
    $mime = $info['mime'];

    switch ($mime) {
        case 'image/jpeg':
            if (!function_exists('imagecreatefromjpeg')) return false;
            $srcImg = imagecreatefromjpeg($src);
            break;
        case 'image/png':
            if (!function_exists('imagecreatefrompng')) return false;
            $srcImg = imagecreatefrompng($src);
            break;
        case 'image/gif':
            if (!function_exists('imagecreatefromgif')) return false;
            $srcImg = imagecreatefromgif($src);
            break;
        case 'image/webp':
            if (!function_exists('imagecreatefromwebp')) return false;
            $srcImg = imagecreatefromwebp($src);
            break;
        default:
            return false;
    }

    // Calculate new size preserving aspect ratio
    $ratio = $width / $height;
    $targetW = $maxW;
    $targetH = intval($targetW / $ratio);
    if ($targetH > $maxH) {
        $targetH = $maxH;
        $targetW = intval($targetH * $ratio);
    }

    // If image already smaller than target, just copy
    if ($width <= $targetW && $height <= $targetH) {
        return copy($src, $dst);
    }

    $dstImg = imagecreatetruecolor($targetW, $targetH);
    // Preserve transparency for PNG and GIF
    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagecolortransparent($dstImg, imagecolorallocatealpha($dstImg, 0, 0, 0, 127));
        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);
    }

    imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $targetW, $targetH, $width, $height);

    $saved = false;
    switch ($mime) {
        case 'image/jpeg':
            $saved = imagejpeg($dstImg, $dst, 85);
            break;
        case 'image/png':
            $saved = imagepng($dstImg, $dst, 6);
            break;
        case 'image/gif':
            $saved = imagegif($dstImg, $dst);
            break;
        case 'image/webp':
            $saved = imagewebp($dstImg, $dst, 80);
            break;
    }

    imagedestroy($srcImg);
    imagedestroy($dstImg);
    return $saved;
}

$variants = [];
// create medium (max 1024x768) and thumb (max 300x200) ONLY for images
if (strpos($mime, 'image/') === 0) {
    $pathInfo = pathinfo($dest);
    $baseName = $pathInfo['filename'];
    $ext = isset($pathInfo['extension']) ? $pathInfo['extension'] : '';
    $mediumName = $baseName . '_md' . ($ext ? '.' . $ext : '');
    $thumbName = $baseName . '_thumb' . ($ext ? '.' . $ext : '');
    $mediumPath = $uploadsDir . DIRECTORY_SEPARATOR . $mediumName;
    $thumbPath = $uploadsDir . DIRECTORY_SEPARATOR . $thumbName;

    if (create_resized_variant($dest, $mediumPath, 1024, 768)) {
        $variants['medium'] = str_replace('\\', '/', substr($mediumPath, strlen($projectRoot) + 1));
    }
    if (create_resized_variant($dest, $thumbPath, 300, 200)) {
        $variants['thumb'] = str_replace('\\', '/', substr($thumbPath, strlen($projectRoot) + 1));
    }
}

echo json_encode(['path' => $rel, 'variants' => $variants]);
?>
