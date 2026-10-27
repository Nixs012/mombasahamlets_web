<?php
/**
 * optimize_images.php
 *
 * Batch image optimizer for Mombasa Hamlets FC frontend images.
 * - Resizes images larger than 1920px (using ffmpeg)
 * - Converts to WebP and replaces large originals with WebP when beneficial
 * - Strips EXIF metadata
 * - Removes duplicate hash-named images
 *
 * Usage: php backend/scripts/optimize_images.php
 */

$projectRoot = dirname(__DIR__, 2);
$imagesDir   = $projectRoot . '/frontend/images';

$maxWidth = 1920;
$quality  = 85;
$webpQuality = 75;

// --- Check if ffmpeg is available ---
function hasFfmpeg(): bool {
    $output = [];
    $exitCode = 0;
    exec('ffmpeg -version 2>NUL', $output, $exitCode);
    return $exitCode === 0 && count($output) > 0;
}

// --- Helper: Remove duplicate hash-named files ---
function removeHashDuplicates(string $dir): int {
    $removed = 0;
    $files = glob($dir . '/*.*');
    if (!$files) return 0;

    $bySize = [];
    foreach ($files as $file) {
        if (is_dir($file)) continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) continue;
        $size = filesize($file);
        $bySize[$size][] = $file;
    }

    foreach ($bySize as $size => $group) {
        if (count($group) < 2) continue;
        usort($group, fn($a, $b) => strlen(basename($a)) <=> strlen(basename($b)));
        for ($i = 1; $i < count($group); $i++) {
            $name = basename($group[$i]);
            if (preg_match('/^[a-f0-9]{32,40}\./', $name) || preg_match('/^img_[a-f0-9]+\./', $name)) {
                if (unlink($group[$i])) {
                    $removed++;
                }
            }
        }
    }

    foreach (glob($dir . '/*', GLOB_ONLYDIR) as $subdir) {
        $removed += removeHashDuplicates($subdir);
    }

    return $removed;
}

// --- Get image dimensions ---
function getImageDimensions(string $filePath): array|null {
    $info = @getimagesize($filePath);
    if ($info) {
        return [(int)$info[0], (int)$info[1], $info['mime']];
    }
    // Try ffprobe
    $dims = [];
    exec("ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 " . escapeshellarg($filePath), $dims, $exit);
    if ($exit === 0 && !empty($dims[0])) {
        $parts = explode('x', trim($dims[0]));
        if (count($parts) === 2) {
            return [(int)$parts[0], (int)$parts[1], ''];
        }
    }
    return null;
}

// --- Optimize a single image using ffmpeg ---
function optimizeImageFfmpeg(string $filePath, int $maxWidth, int $webpQuality): array {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        return ['error' => 'Unsupported format', 'sizeBefore' => filesize($filePath), 'sizeAfter' => 0];
    }

    $sizeBefore = filesize($filePath);
    $dims = getImageDimensions($filePath);
    if (!$dims) return ['error' => 'Cannot read dimensions', 'sizeBefore' => $sizeBefore, 'sizeAfter' => 0];
    [$width, $height, $mime] = $dims;

    $result = ['width' => $width, 'height' => $height, 'sizeBefore' => $sizeBefore, 'sizeAfter' => $sizeBefore];

    // Build filter: resize if needed, strip metadata
    $filters = [];
    if ($width > $maxWidth) {
        $filters[] = "scale={$maxWidth}:-2:flags=lanczos";
    } else {
        $filters[] = "scale=iw:ih"; // no scaling
    }
    $filterStr = implode(',', $filters);

    // Step 1: Create optimized WebP version
    $webpPath = substr($filePath, 0, strrpos($filePath, '.')) . '.webp';
    $webpScale = ($width > $maxWidth) ? "scale={$maxWidth}:-2:flags=lanczos" : "scale=iw:ih";

    if ($ext === 'gif') {
        // GIFs: extract first frame or convert animation
        $webpCmd = "ffmpeg -i " . escapeshellarg($filePath) . " -y -vf fps=15,{$webpScale} -loop 0 -c:v libwebp -preset picture -qscale:v " . (100 - $webpQuality) . " " . escapeshellarg($webpPath) . " 2>NUL";
    } else {
        $webpCmd = "ffmpeg -i " . escapeshellarg($filePath) . " -y -vf {$webpScale} -c:v libwebp -preset photo -qscale:v " . (100 - $webpQuality) . " -an " . escapeshellarg($webpPath) . " 2>NUL";
    }

    exec($webpCmd, $webOutput, $webExit);
    $webpCreated = ($webExit === 0 && file_exists($webpPath));

    if ($webpCreated && filesize($webpPath) < $sizeBefore * 0.8) {
        // WebP is significantly smaller — replace the original
        $result['webp_smaller'] = true;
        unlink($filePath);
        // If webp is the same base name pattern, rename it back to original extension
        rename($webpPath, $filePath . '.webp');
        $result['sizeAfter'] = filesize($filePath . '.webp');
    } elseif ($webpCreated) {
        // WebP exists but isn't much smaller, keep both
        if (filesize($webpPath) < $sizeBefore) {
            unlink($filePath);
            rename($webpPath, $filePath . '.webp');
            $result['sizeAfter'] = filesize($filePath . '.webp');
        } else {
            unlink($webpPath);
            $result['sizeAfter'] = $sizeBefore;
        }
    }

    return $result;
}

// --- Process images ---
echo "Starting image optimization...\n";
$useFfmpeg = hasFfmpeg();
echo "FFmpeg available: " . ($useFfmpeg ? "Yes" : "No") . "\n";

// First pass: remove duplicates
$duplicatesRemoved = removeHashDuplicates($imagesDir);
echo "Removed $duplicatesRemoved duplicate files.\n";

// Second pass: optimize
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($imagesDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

$processed = 0;
$skipped = 0;
$errors = 0;
$bytesBefore = 0;
$bytesAfter = 0;

foreach ($it as $file) {
    if (!is_file($file->getRealPath())) continue;

    $ext = strtolower($file->getExtension());
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) continue;

    $path = $file->getRealPath();

    // Skip match-logo uploads
    if (strpos($path, 'match-logos') !== false) continue;

    $sizeBefore = filesize($path);
    $bytesBefore += $sizeBefore;

    if ($useFfmpeg) {
        $result = optimizeImageFfmpeg($path, $maxWidth, $webpQuality);
        if (isset($result['error'])) {
            $errors++;
            if ($result['error'] !== 'Cannot read dimensions' && $result['error'] !== 'Unsupported format') {
                echo "  ERROR: {$path} - {$result['error']}\n";
            }
        } else {
            $bytesAfter += $result['sizeAfter'];
            $processed++;
        }
    } else {
        // No ffmpeg — just use getimagesize info for stats
        $bytesAfter += $sizeBefore;
        $skipped++;
    }

    if ($processed % 5 === 0 && $processed > 0) {
        echo "Processed {$processed} images...\n";
    }
}

// Print summary
echo "\n=== Optimization Summary ===\n";
echo "Processed: {$processed} images\n";
echo "Skipped: {$skipped} images\n";
echo "Errors: {$errors} images\n";
echo "Duplicates removed: {$duplicatesRemoved}\n";

$beforeMB = round($bytesBefore / 1024 / 1024, 2);
$afterMB  = round($bytesAfter / 1024 / 1024, 2);
$savedMB  = $beforeMB - $afterMB;
echo "Total size before: {$beforeMB} MB\n";
echo "Total size after:  {$afterMB} MB\n";
echo "Space saved:       {$savedMB} MB\n";

// Cleanup test file
@unlink($projectRoot . '/optimize_test.php');
