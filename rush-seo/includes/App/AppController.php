<?php
namespace RushSEO\App;

use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
	exit;
}

class AppController {
	public static function index(WP_REST_Request $request): WP_REST_Response
	{
		$distDir = RUSH_SEO_PLUGIN_DIR . 'admin-ui/dist';
		$index = $distDir . '/index.html';
		if (file_exists($index)) {
			$html = file_get_contents($index);
		} else {
			$html = '<!DOCTYPE html><html><head><meta charset="utf-8" />'
				. '<meta name="viewport" content="width=device-width, initial-scale=1" />'
				. '<title>Rush SEO</title>'
				. '</head><body>'
				. '<div style="padding:16px;font-family:Inter,system-ui,sans-serif">'
				. '<h1>Rush SEO</h1><p>Build the admin UI: cd admin-ui && npm i && npm run build</p>'
				. '</div></body></html>';
		}
		$response = new WP_REST_Response($html, 200);
		$response->header('Content-Type', 'text/html; charset=utf-8');
		return $response;
	}
}

