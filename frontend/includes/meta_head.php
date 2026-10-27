<?php
/**
 * frontend/includes/meta_head.php
 *
 * Shared HTML <head> section with configurable meta tags, Open Graph tags,
 * CSS includes, favicons, and API configuration.
 *
 * Usage (place at the top of each frontend PHP page, BEFORE any HTML output):
 *   <?php
 *   require_once __DIR__ . '/config/asset_helper.php';
 *   $pageTitle       = "Mombasa Hamlets FC - Home";
 *   $pageDescription = "Official website of Mombasa Hamlets Football Club...";
 *   $pageImage       = "images/logo1.webp";
 *   $pageKeywords    = "Mombasa Hamlets FC, football, Kenya";
 *   $canonicalUrl    = null; // will be auto-computed if not set
 *   include __DIR__ . '/includes/meta_head.php';
 *   ?>
 *
 * Variables consumed:
 *   @var string|null $pageTitle      Page title (defaults to site name)
 *   @var string|null $pageDescription Meta description (defaults to site description)
 *   @var string|null $pageImage       OG image path (relative to frontend/)
 *   @var string|null $pageKeywords    Meta keywords (optional)
 *   @var string|null $canonicalUrl    Canonical URL (optional, for SEO)
 *   @var string|null $extraCss        Extra CSS files to include (array)
 */

// Ensure config is loaded
if (!function_exists('asset_url')) {
    require_once __DIR__ . '/../config/asset_helper.php';
}
require_once __DIR__ . '/../config/error_settings.php';

// Determine the correct base path for CSS/JS assets relative to the current page
// All frontend pages are in frontend/ so paths are relative to that
$appEnv = getenv('APP_ENV') ?: 'development';

// Build the full base URL for absolute links (Open Graph, canonical)
if (isset($GLOBALS['BASE_URL']) && !empty($GLOBALS['BASE_URL'])) {
    $fullBaseUrl = $GLOBALS['BASE_URL'];
} else {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $projectRoot = preg_replace('#/frontend$#', '', $scriptDir);
    if ($projectRoot === '/' || $projectRoot === '.') $projectRoot = '';
    $fullBaseUrl = rtrim($scheme . $host . $projectRoot, '');
}

// Default values
$pageTitle       = $pageTitle       ?? 'Mombasa Hamlets FC';
$pageDescription = $pageDescription ?? 'Mombasa Hamlets Football Club official website. Latest news, fixtures, results, player profiles, and merchandise from Mombasa, Kenya.';
$pageImage       = $pageImage       ?? 'images/logo1.webp';
$pageKeywords    = $pageKeywords    ?? 'Mombasa Hamlets FC, football, Kenya, Premier League, sports, Mombasa';
$canonicalUrl    = $canonicalUrl    ?? ($fullBaseUrl . '/frontend/' . basename($_SERVER['SCRIPT_NAME'] ?? 'index.php', '.php'));
$pageImageFull   = $fullBaseUrl . '/frontend/' . $pageImage;

// Version for cache busting
$assetVersion = $appEnv === 'production' ? 'prod' : 'dev';

// CSS paths - design-system is in root css/ (one level up), style.css is in frontend/css/
// For production, use minified assets from dist/
$designSystemCss = '../css/design-system.css';
$styleCss        = $appEnv === 'production' ? 'dist/css/style.min.css' : 'css/style.css';

// JS config for frontend
$apiConfigJs     = 'js/api-config.js';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($pageKeywords): ?>
    <meta name="keywords" content="<?php echo htmlspecialchars($pageKeywords, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>

    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($pageImageFull, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image:alt" content="<?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Mombasa Hamlets FC">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($pageImageFull, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Favicons - placed in frontend/images/ -->
    <link rel="icon" href="../images/favicon.ico" sizes="any">
    <link rel="icon" type="image/svg+xml" href="../images/favicon.svg">
    <link rel="apple-touch-icon" href="../images/apple-touch-icon.png">
    <link rel="manifest" href="../images/manifest.json">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo $designSystemCss; ?>?v=<?php echo $assetVersion; ?>">
    <link rel="stylesheet" href="<?php echo $styleCss; ?>?v=<?php echo $assetVersion; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <?php if (isset($extraCss)): ?>
        <?php foreach ($extraCss as $cssFile): 
            // Use minified version in production if available
            if ($appEnv === 'production') {
                $cssBase = basename($cssFile, '.css');
                $minPath = 'dist/css/' . $cssBase . '.min.css';
                if (file_exists(__DIR__ . '/../dist/css/' . $cssBase . '.min.css')) {
                    echo '<link rel="stylesheet" href="' . $minPath . '?v=' . $assetVersion . '">' . "\n";
                } else {
                    echo '<link rel="stylesheet" href="' . $cssFile . '?v=' . $assetVersion . '">' . "\n";
                }
            } else {
        ?>
    <link rel="stylesheet" href="<?php echo $cssFile; ?>?v=<?php echo $assetVersion; ?>">
        <?php } endforeach; ?>
    <?php endif; ?>

    <!-- Theme color -->
    <meta name="theme-color" content="#DAA520">
    <meta name="msapplication-TileColor" content="#0A192F">
</head>