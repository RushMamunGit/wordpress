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
		$html = '<!DOCTYPE html><html><head><meta charset="utf-8" />'
			. '<meta name="viewport" content="width=device-width, initial-scale=1" />'
			. '<title>Rush SEO</title>'
			. '<link rel="stylesheet" href="https://unpkg.com/@shopify/polaris@12.8.0/build/esm/styles.css" />'
			. '</head><body>'
			. '<div id="app" style="padding:16px;font-family:Inter,system-ui,sans-serif">'
			. '<h1>Rush SEO</h1>'
			. '<p>Embedded app shell is running. Build UI bundle next.</p>'
			. '</div>'
			. '<script src="https://unpkg.com/@shopify/app-bridge@3"></script>'
			. '</body></html>';
		$response = new WP_REST_Response($html, 200);
		$response->header('Content-Type', 'text/html; charset=utf-8');
		return $response;
	}
}

