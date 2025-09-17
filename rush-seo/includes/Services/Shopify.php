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

	public static function getTokenForShop(string $shop): ?string
	{
		global $wpdb;
		$table = $wpdb->prefix . 'rush_seo_shops';
		$enc = $wpdb->get_var($wpdb->prepare("SELECT access_token_enc FROM {$table} WHERE shop_domain = %s AND status = 'active'", $shop));
		if (!$enc) { return null; }
		return Crypto::decrypt((string) $enc);
	}

	public static function rest(string $shop, string $method, string $path, array $opts = [])
	{
		$token = self::getTokenForShop($shop);
		if (!$token) { return new WP_Error('no_token', 'No token for shop.'); }
		$url = sprintf('https://%s/admin/api/2024-07/%s', $shop, ltrim($path, '/'));
		$args = [
			'headers' => [
				'X-Shopify-Access-Token' => $token,
				'Content-Type' => 'application/json',
			],
			'timeout' => 20,
		];
		if (!empty($opts['body'])) { $args['body'] = wp_json_encode($opts['body']); }
		$response = null;
		switch (strtoupper($method)) {
			case 'GET': $response = wp_remote_get($url, $args); break;
			case 'POST': $response = wp_remote_post($url, $args); break;
			case 'PUT': $args['method'] = 'PUT'; $response = wp_remote_request($url, $args); break;
			default: return new WP_Error('bad_method', 'Unsupported method');
		}
		if (is_wp_error($response)) { return $response; }
		$status = wp_remote_retrieve_response_code($response);
		$body = json_decode(wp_remote_retrieve_body($response), true);
		if ($status >= 200 && $status < 300) { return $body; }
		return new WP_Error('rest_error', 'Shopify REST error', [ 'status' => $status, 'body' => $body ]);
	}

	public static function graphql(string $shop, string $query, array $variables = [])
	{
		$token = self::getTokenForShop($shop);
		if (!$token) { return new WP_Error('no_token', 'No token for shop.'); }
		$url = sprintf('https://%s/admin/api/2024-07/graphql.json', $shop);
		$args = [
			'headers' => [
				'X-Shopify-Access-Token' => $token,
				'Content-Type' => 'application/json',
			],
			'timeout' => 20,
			'body' => wp_json_encode([ 'query' => $query, 'variables' => $variables ]),
		];
		$response = wp_remote_post($url, $args);
		if (is_wp_error($response)) { return $response; }
		$status = wp_remote_retrieve_response_code($response);
		$body = json_decode(wp_remote_retrieve_body($response), true);
		if ($status >= 200 && $status < 300) { return $body; }
		return new WP_Error('graphql_error', 'Shopify GraphQL error', [ 'status' => $status, 'body' => $body ]);
	}

	public static function getResource(string $shop, string $type, string $id): array
	{
		switch ($type) {
			case 'product':
				$res = self::rest($shop, 'GET', "products/{$id}.json");
				return (array) ($res['product'] ?? []);
			case 'page':
				$res = self::rest($shop, 'GET', "pages/{$id}.json");
				return (array) ($res['page'] ?? []);
			case 'article':
				$res = self::rest($shop, 'GET', "articles/{$id}.json");
				return (array) ($res['article'] ?? []);
			default:
				return [];
		}
	}

	public static function updateResource(string $shop, string $type, string $id, array $payload): bool
	{
		switch ($type) {
			case 'product':
				$body = [ 'product' => array_merge([ 'id' => (int) $id ], $payload) ];
				$res = self::rest($shop, 'PUT', "products/{$id}.json", [ 'body' => $body ]);
				return !is_wp_error($res);
			case 'page':
				$body = [ 'page' => array_merge([ 'id' => (int) $id ], $payload) ];
				$res = self::rest($shop, 'PUT', "pages/{$id}.json", [ 'body' => $body ]);
				return !is_wp_error($res);
			case 'article':
				$body = [ 'article' => array_merge([ 'id' => (int) $id ], $payload) ];
				$res = self::rest($shop, 'PUT', "articles/{$id}.json", [ 'body' => $body ]);
				return !is_wp_error($res);
			default:
				return false;
		}
	}
}

