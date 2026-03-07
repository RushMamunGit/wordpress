<?php
namespace RushSEO\Rest;

use RushSEO\Services\Jobs;
use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
	exit;
}

class ScanController {
	public static function enqueue(WP_REST_Request $request): WP_REST_Response
	{
		$shop = sanitize_text_field((string) $request->get_param('shop'));
		$targetType = sanitize_text_field((string) $request->get_param('target_type'));
		$targetRef = sanitize_text_field((string) $request->get_param('target_ref'));
		if (empty($shop) || empty($targetType)) {
			return new WP_REST_Response([ 'error' => 'missing_params' ], 400);
		}
		$jobId = Jobs::enqueue('scan', [ 'target_type' => $targetType, 'target_ref' => $targetRef ], $shop);
		return new WP_REST_Response([ 'ok' => true, 'jobId' => $jobId ], 200);
	}
}

