<?php
namespace RushSEO\Rest;

use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
	exit;
}

class BillingController {
	public static function activate(WP_REST_Request $request): WP_REST_Response
	{
		$shop = sanitize_text_field((string) $request->get_param('shop'));
		if (empty($shop)) {
			return new WP_REST_Response([
				'error' => 'missing_shop',
				'message' => 'Shop domain is required.'
			], 400);
		}

		global $wpdb;
		$table = $wpdb->prefix . 'rush_seo_shops';
		$updated = $wpdb->update($table, [
			'plan' => 'active',
			'status' => 'active',
		], [ 'shop_domain' => $shop ], [ '%s', '%s' ], [ '%s' ]);

		return new WP_REST_Response([
			'ok' => true,
			'shop' => $shop,
			'updated' => (bool) $updated,
		], 200);
	}
}

