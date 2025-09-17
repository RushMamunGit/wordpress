<?php
namespace RushSEO\Rest;

use RushSEO\Services\Theme;
use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
	exit;
}

class ThemeController {
	public static function verification(WP_REST_Request $request): WP_REST_Response
	{
		$shop = sanitize_text_field((string) $request->get_param('shop'));
		$codes = (array) $request->get_param('codes');
		if (empty($shop)) { return new WP_REST_Response([ 'error' => 'missing_shop' ], 400); }
		$ok = Theme::installVerificationMeta($shop, $codes);
		return new WP_REST_Response([ 'ok' => (bool) $ok ], 200);
	}

	public static function schema(WP_REST_Request $request): WP_REST_Response
	{
		$shop = sanitize_text_field((string) $request->get_param('shop'));
		$types = (array) $request->get_param('types');
		if (empty($shop)) { return new WP_REST_Response([ 'error' => 'missing_shop' ], 400); }
		$ok = Theme::installSchemaSnippets($shop, $types);
		return new WP_REST_Response([ 'ok' => (bool) $ok ], 200);
	}
}

