<?php
/**
 * frontend/config/asset_helper.php
 *
 * Provides helper functions to generate correct asset URLs
 * that work in both development and production.
 * In production: serves minified files from /frontend/dist/
 * In development: serves original unminified files
 */

require_once __DIR__ . '/../../backend/config/env_loader.php';

if (!function_exists('asset_url')) {
    /**
     * Generate an asset URL. In production, uses minified versions.
     *
     * @param string $path Asset path relative to frontend/ (e.g. "css/style.css", "js/main.js")
     * @return string  URL path suitable for href/src attributes
     */
    function asset_url(string $path): string {
        // Normalize path
        $path = ltrim($path, '/');

        $appEnv = getenv('APP_ENV') ?: 'development';

        if ($appEnv === 'production') {
            // Use minified version
            $minPath = preg_replace('/\.([a-z]+)$/', '.min.\$1', $path);
            $minFile = __DIR__ . '/../dist/' . $minPath;
            if (file_exists($minFile)) {
                return '/frontend/dist/' . $minPath;
            }
        }

        // Development (or no minified version available): use original
        return '/frontend/' . $path;
    }
}

if (!function_exists('asset_css')) {
    /** Shorthand for CSS files */
    function asset_css(string $path): string {
        return asset_url($path);
    }
}

if (!function_exists('asset_js')) {
    /** Shorthand for JS files */
    function asset_js(string $path): string {
        return asset_url($path);
    }
}

if (!function_exists('asset_meta')) {
    /**
     * Output a meta tag.
     */
    function asset_meta(string $name, string $content): string {
        return '<meta name="' . htmlspecialchars($name, ENT_QUOTES) . '" content="' . htmlspecialchars($content, ENT_QUOTES) . '">';
    }
}

if (!function_exists('og_meta')) {
    /**
     * Output an Open Graph meta tag.
     */
    function og_meta(string $property, string $content): string {
        return '<meta property="' . htmlspecialchars($property, ENT_QUOTES) . '" content="' . htmlspecialchars($content, ENT_QUOTES) . '">';
    }
}

// Expose base URL to templates
$GLOBALS['base_url'] = (getenv('BASE_URL') && getenv('BASE_URL') !== '') ? rtrim(getenv('BASE_URL'), '/') : '';
