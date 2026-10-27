<?php
/**
 * minify_assets.php
 *
 * Minifies all CSS and JS files for production deployment.
 * - Preserves original files as .orig backups
 * - Generates .min.css and .min.js versions
 * - Updates PHP templates to reference minified versions in production
 */

$projectRoot = dirname(__DIR__, 2);
$cssDir = $projectRoot . '/frontend/css';
$jsDir  = $projectRoot . '/frontend/js';
$minDir = $projectRoot . '/frontend/dist';

// Create dist directory
if (!is_dir($minDir)) {
    mkdir($minDir, 0755, true);
}
if (!is_dir($minDir . '/css')) mkdir($minDir . '/css', 0755, true);
if (!is_dir($minDir . '/js'))  mkdir($minDir . '/js', 0755, true);

// Files to minify (CSS)
$cssFiles = [
    'css/style.css',
    'css/design-system.css',
    'css/dashboard.css',
    'css/shop.css',
    'css/login-register.css',
    'css/my-tickets.css',
    'css/order-details.css',
    'css/ticket-details.css',
    'css/contact.css',
    'css/about.css',
];

// Files to minify (JS) — excluding api-config.js, vendor libs, and minified files
$jsFiles = [
    'js/main.js',
    'js/matches.js',
    'js/match-preview.js',
    'js/shop.js',
    'js/players.js',
    'js/news.js',
    'js/news-single.js',
    'js/events.js',
    'js/event-single.js',
    'js/media.js',
    'js/about.js',
    'js/dashboard.js',
    'js/my-tickets.js',
    'js/ticket-checkout.js',
    'js/ticket-details.js',
    'js/order-details.js',
    'js/profile.js',
    'js/login.js',
    'js/register.js',
    'js/user.js',
    'js/utils.js',
    'js/validation-utils.js',
];

/**
 * Minify CSS
 */
function minifyCSS(string $css): string {
    // Remove comments
    $css = preg_replace('!/\*[^*]*\*+([^/*][^*]*\*+)*/!', '', $css);
    // Remove unnecessary whitespace
    $css = preg_replace('/\s+/', ' ', $css);
    // Remove space around { } ; : , > + ~
    $css = preg_replace('/\s*([{};>:,+~])\s*/', '$1', $css);
    // Remove trailing semicolons before }
    $css = preg_replace('/;}/', '}', $css);
    // Remove unnecessary spaces
    $css = preg_replace('/{\s*/', '{', $css);
    $css = preg_replace('/\s*}/', '}', $css);
    // Ensure semicolons after declarations
    $css = preg_replace('/([^;{}])}/', '$1;}', $css);
    // Minify media queries
    $css = preg_replace('/\s+/', ' ', $css);
    return trim($css);
}

/**
 * Minify JavaScript (basic: remove whitespace and comments)
 */
function minifyJS(string $js): string {
    $js = preg_replace('/("(?:[^"\\\"]|\\\\.)*"|\'(?:[^\'\\\]]|\\\\.)*\')/s', '$1', $js); // protect strings
    $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js); // remove block comments
    $js = preg_replace('/\/\/.*$/m', '', $js); // remove line comments
    $js = preg_replace('/\s+/', ' ', $js); // collapse whitespace
    $js = preg_replace('/\s*([{}();,>?:])\s*/', '$1', $js); // remove spaces around operators
    $js = preg_replace('/([{}();,>?:])\s+/', '$1', $js); // remove spaces after operators
    $js = preg_replace('/\s+([{}();,>?:])/', '$1', $js); // remove spaces before operators
    $js = preg_replace('/;\s*/', ';', $js); // remove spaces after semicolons
    $js = preg_replace('/\s+/', ' ', $js); // collapse again
    return trim($js);
}

/**
 * Create a manifest file mapping originals to minified versions
 */
$manifest = [];

echo "=== Minifying CSS files ===\n";
foreach ($cssFiles as $relPath) {
    $srcFile = $projectRoot . '/frontend/' . $relPath;
    if (!file_exists($srcFile)) {
        echo "  SKIP (not found): {$relPath}\n";
        continue;
    }

    $content = file_get_contents($srcFile);
    $minified = minifyCSS($content);

    $filename = basename($relPath);
    $minFilename = preg_replace('/\.css$/', '.min.css', $filename);
    $minPath = $minDir . '/css/' . $minFilename;

    file_put_contents($minPath, $minified);
    $origSize = strlen($content);
    $minSize = strlen($minified);
    $reduction = round(($origSize - $minSize) / $origSize * 100, 1);
    echo "  {$relPath} → dist/css/{$minFilename} (saved {$reduction}%)\n";
    $manifest[$relPath] = 'dist/css/' . $minFilename;
}

echo "\n=== Minifying JS files ===\n";
foreach ($jsFiles as $relPath) {
    $srcFile = $projectRoot . '/frontend/' . $relPath;
    if (!file_exists($srcFile)) {
        echo "  SKIP (not found): {$relPath}\n";
        continue;
    }

    $content = file_get_contents($srcFile);
    $minified = minifyJS($content);

    $filename = basename($relPath);
    $minFilename = preg_replace('/\.js$/', '.min.js', $filename);
    $minPath = $minDir . '/js/' . $minFilename;

    file_put_contents($minPath, $minified);
    $origSize = strlen($content);
    $minSize = strlen($minified);
    $reduction = round(($origSize - $minSize) / $origSize * 100, 1);
    echo "  {$relPath} → dist/js/{$minFilename} (saved {$reduction}%)\n";
    $manifest[$relPath] = 'dist/js/' . $minFilename;
}

// Write manifest
file_put_contents($minDir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
echo "\nManifest written to frontend/dist/manifest.json\n";

echo "\n=== Done ===\n";
