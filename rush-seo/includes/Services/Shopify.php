<?php
namespace RushSEO\Services;

use WP_Error;

if (!defined('ABSPATH')) {
	exit;
}

class Shopify {
	public static function exchangeAuthorizationCodeForToken(string $shop, string $code, string $apiKey, string $apiSecret)
	{
		$endpoint = sprintf('https://%s/admin/oauth/access_token', $shop);
		$args = [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'timeout' => 20,
			'body' => wp_json_encode([
				'client_id' => $apiKey,
				'client_secret' => $apiSecret,
				'code' => $code,
			]),
		];
		$response = wp_remote_post($endpoint, $args);
		if (is_wp_error($response)) {
			return $response;
		}
		$status = wp_remote_retrieve_response_code($response);
		$body = json_decode(wp_remote_retrieve_body($response), true);
		if ($status !== 200 || empty($body['access_token'])) {
			return new WP_Error('token_exchange_failed', 'Failed to exchange authorization code for access token.', [ 'status' => $status, 'body' => $body ]);
		}
		return $body['access_token'];
	}
}

