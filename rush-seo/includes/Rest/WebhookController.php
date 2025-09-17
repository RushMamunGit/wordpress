<?php
namespace RushSEO\Rest;

use RushSEO\Services\Hmac;
use RushSEO\Services\Crypto;
use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
	exit;
}

class WebhookController {
	public static function handle(WP_REST_Request $request): WP_REST_Response
	{
		$topic = $request->get_header('x-shopify-topic');
		$hmac = $request->get_header('x-shopify-hmac-sha256');
		$shop = $request->get_header('x-shopify-shop-domain');
		$sharedSecret = defined('RUSH_SEO_SHOPIFY_API_SECRET') ? constant('RUSH_SEO_SHOPIFY_API_SECRET') : '';
		$raw = $request->get_body();
		if (empty($sharedSecret) || empty($hmac) || empty($topic)) {
			return new WP_REST_Response([ 'error' => 'invalid_headers' ], 400);
		}
		if (!Hmac::verifyWebhook($raw, $hmac, $sharedSecret)) {
			return new WP_REST_Response([ 'error' => 'invalid_hmac' ], 401);
		}

		if ($topic === 'app/uninstalled' && !empty($shop)) {
			global $wpdb;
			$table = $wpdb->prefix . 'rush_seo_shops';
			$wpdb->update($table, [ 'status' => 'uninstalled' ], [ 'shop_domain' => $shop ], [ '%s' ], [ '%s' ]);
		}

		return new WP_REST_Response([ 'ok' => true ], 200);
	}
}

