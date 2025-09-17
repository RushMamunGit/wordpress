<?php
namespace RushSEO\Rest;

use RushSEO\Services\AI;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
	exit;
}

class AiController {
	public static function suggest(WP_REST_Request $request): WP_REST_Response
	{
		$type = sanitize_text_field((string) $request->get_param('type'));
		$context = (string) $request->get_param('context');
		$constraints = $request->get_param('constraints');
		if (empty($type) || empty($context)) {
			return new WP_REST_Response([ 'error' => 'missing_params' ], 400);
		}
		switch ($type) {
			case 'title':
				$result = AI::suggestTitle($context, is_array($constraints) ? $constraints : []);
				break;
			case 'meta':
				$result = AI::suggestMetaDescription($context, is_array($constraints) ? $constraints : []);
				break;
			case 'alt':
					$result = AI::suggestAlt($context, is_array($constraints) ? $constraints : []);
				break;
			default:
				return new WP_REST_Response([ 'error' => 'invalid_type' ], 400);
		}
		if (is_wp_error($result)) {
			return new WP_REST_Response([ 'error' => $result->get_error_code(), 'message' => $result->get_error_message() ], 502);
		}
		return new WP_REST_Response([ 'ok' => true, 'suggestion' => $result ], 200);
	}
}

