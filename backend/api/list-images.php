<?php
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=UTF-8');

$frontendBase = realpath(__DIR__ . '/../../frontend');
if (!$frontendBase) {
    echo json_encode([]);
    exit;
}

// Only show images from the uploads directory (user-uploaded images)
// Exclude default/system images from other directories
$uploadsDir = realpath($frontendBase . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'uploads');
if (!$uploadsDir || !is_dir($uploadsDir)) {
    echo json_encode([]);
    exit;
}

// Collected only image files from the uploads directory
$allFiles = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadsDir));
foreach ($it as $file) {
    if ($file->isFile() && $file->isReadable()) {
        $filename = $file->getFilename();
        
        // Skip hidden variants (_md, _thumb) from the main list unless explicitly requested
        if (preg_match('/_(md|thumb)\.[a-z0-9]+$/i', $filename)) {
            continue;
        }

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, ['png','jpg','jpeg','gif','svg','webp', 'mp4', 'webm', 'ogg', 'mov'])) {
            $realPath = $file->getRealPath();
            if ($realPath && file_exists($realPath) && is_readable($realPath)) {
                if (strpos($realPath, $uploadsDir) === 0) {
                    $rel = str_replace('\\', '/', substr($realPath, strlen($frontendBase) + 1));
                    $allFiles[] = $rel;
                }
            }
        }
    }
}

// Sort files by modified time (newest first)
usort($allFiles, function($a, $b) use ($frontendBase) {
    return filemtime($frontendBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $b)) - 
           filemtime($frontendBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $a));
});

// Pagination
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$total = count($allFiles);

if ($limit > 0) {
    $offset = ($page - 1) * $limit;
    $files = array_slice($allFiles, $offset, $limit);
} else {
    $files = $allFiles;
}

echo json_encode([
    'total' => $total,
    'page' => $page,
    'limit' => $limit,
    'pages' => $limit > 0 ? ceil($total / $limit) : 1,
    'images' => array_values($files)
]);
?>
