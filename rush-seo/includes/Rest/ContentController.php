<?php
namespace RushSEO\Rest;

use RushSEO\Services\Shopify;
use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
	exit;
}

class ContentController {
	public static function get(WP_REST_Request $request): WP_REST_Response
	{
		$shop = sanitize_text_field((string) $request->get_param('shop'));
		$type = sanitize_text_field((string) $request->get_param('type'));
		$id = (string) $request->get_param('id');
		if (empty($shop) || empty($type) || empty($id)) {
			return new WP_REST_Response([ 'error' => 'missing_params' ], 400);
		}
		$data = Shopify::getResource($shop, $type, $id);
		return new WP_REST_Response([ 'ok' => true, 'data' => $data ], 200);
	}

	public static function update(WP_REST_Request $request): WP_REST_Response
	{
		$shop = sanitize_text_field((string) $request->get_param('shop'));
		$type = sanitize_text_field((string) $request->get_param('type'));
		$id = (string) $request->get_param('id');
		$payload = $request->get_param('payload');
		if (empty($shop) || empty($type) || empty($id) || empty($payload)) {
			return new WP_REST_Response([ 'error' => 'missing_params' ], 400);
		}
		$updated = Shopify::updateResource($shop, $type, $id, (array) $payload);
		return new WP_REST_Response([ 'ok' => true, 'updated' => (bool) $updated ], 200);
	}
}

