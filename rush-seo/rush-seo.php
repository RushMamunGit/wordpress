<?php
/**
 * Plugin Name: Rush SEO for Shopify
 * Description: Embedded Shopify SEO assistant for products, collections, and pages. AI suggestions, scoring, redirects, schema, verification, robots editor.
 * Version: 0.1.0
 * Author: Rush SEO
 * Requires PHP: 8.0
 * License: GPLv2 or later
 * Text Domain: rush-seo
 */

if (!defined('ABSPATH')) {
	exit;
}

define('RUSH_SEO_VERSION', '0.1.0');
define('RUSH_SEO_PLUGIN_FILE', __FILE__);
define('RUSH_SEO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RUSH_SEO_PLUGIN_URL', plugin_dir_url(__FILE__));

// Optional: Define your Gemini API key via wp-config.php for production.
// define('RUSH_SEO_GEMINI_API_KEY', '');

// PSR-4 like autoloader for the RushSEO namespace.
spl_autoload_register(function ($class) {
	if (strpos($class, 'RushSEO\\') !== 0) {
		return;
	}
	$relative = str_replace(['RushSEO\\', '\\'], ['', '/'], $class) . '.php';
	$path = RUSH_SEO_PLUGIN_DIR . 'includes/' . $relative;
	if (file_exists($path)) {
		require_once $path;
	}
});

// Activation / Deactivation hooks
register_activation_hook(__FILE__, function () {
	\RushSEO\Installer::activate();
});

register_deactivation_hook(__FILE__, function () {
	\RushSEO\Installer::deactivate();
});

// Bootstrap the plugin after other plugins are loaded
add_action('plugins_loaded', function () {
	\RushSEO\Plugin::init();
});

