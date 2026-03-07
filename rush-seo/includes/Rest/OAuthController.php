<?php
namespace RushSEO\Rest;

use RushSEO\Services\Hmac;
use RushSEO\Services\Shopify;
use RushSEO\Services\Crypto;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
	exit;
}

class OAuthController {
	public static function install(WP_REST_Request $request): WP_REST_Response
	{
		$shop = sanitize_text_field((string) $request->get_param('shop'));
		if (empty($shop)) {
			return new WP_REST_Response([
				'error' => 'missing_shop',
				'message' => 'Shop domain is required.'
			], 400);
		}

		// Build Shopify OAuth URL (placeholder; client_id and scopes should be set from settings)
		$clientId = defined('RUSH_SEO_SHOPIFY_API_KEY') ? constant('RUSH_SEO_SHOPIFY_API_KEY') : '';
		$scopes = 'read_products,write_products,read_content,write_content,read_themes,write_themes,read_redirects,write_redirects';
		$redirectUri = rawurlencode(rest_url('rush-seo/v1/oauth/callback'));
		$nonce = wp_generate_password(12, false);
		$oauthUrl = sprintf('https://%s/admin/oauth/authorize?client_id=%s&scope=%s&redirect_uri=%s&state=%s', $shop, rawurlencode($clientId), rawurlencode($scopes), $redirectUri, rawurlencode($nonce));

		return new WP_REST_Response([
			'ok' => true,
			'redirectUrl' => $oauthUrl,
		], 200);
	}

	public static function callback(WP_REST_Request $request): WP_REST_Response
	{
		$params = $request->get_params();
		$shop = isset($params['shop']) ? sanitize_text_field((string) $params['shop']) : '';
		$hmac = isset($params['hmac']) ? (string) $params['hmac'] : '';
		$code = isset($params['code']) ? (string) $params['code'] : '';

		if (empty($shop) || empty($hmac) || empty($code)) {
			return new WP_REST_Response([
				'error' => 'invalid_request',
				'message' => 'Missing required OAuth parameters.'
			], 400);
		}

		$sharedSecret = defined('RUSH_SEO_SHOPIFY_API_SECRET') ? constant('RUSH_SEO_SHOPIFY_API_SECRET') : '';
		if (empty($sharedSecret)) {
			return new WP_REST_Response([
				'error' => 'server_config',
				'message' => 'Server not configured with Shopify API secret.'
			], 500);
		}

		// Verify HMAC signature
		if (!Hmac::verifyFromQuery($params, $sharedSecret)) {
			return new WP_REST_Response([
				'error' => 'invalid_hmac',
				'message' => 'HMAC verification failed.'
			], 401);
		}

		// Exchange code for access token (implemented later)
		$apiKey = defined('RUSH_SEO_SHOPIFY_API_KEY') ? constant('RUSH_SEO_SHOPIFY_API_KEY') : '';
		$accessToken = Shopify::exchangeAuthorizationCodeForToken($shop, $code, $apiKey, $sharedSecret);
		if (is_wp_error($accessToken)) {
			return new WP_REST_Response([
				'error' => 'token_exchange_failed',
				'message' => $accessToken->get_error_message(),
			], 502);
		}

		// Save or update shop record with encrypted token and 14-day trial
		global $wpdb;
		$table = $wpdb->prefix . 'rush_seo_shops';
		$enc = Crypto::encrypt((string) $accessToken);
		$trialEnd = gmdate('Y-m-d H:i:s', time() + 14 * DAY_IN_SECONDS);
		$exists = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM {$table} WHERE shop_domain = %s", $shop));
		if ($exists) {
			$wpdb->update($table, [
				'access_token_enc' => $enc,
				'installed_at' => current_time('mysql'),
				'trial_end_at' => $trialEnd,
				'plan' => 'trial',
				'status' => 'active',
			], [ 'shop_domain' => $shop ], [ '%s', '%s', '%s', '%s', '%s' ], [ '%s' ]);
		} else {
			$wpdb->insert($table, [
				'shop_domain' => $shop,
				'access_token_enc' => $enc,
				'installed_at' => current_time('mysql'),
				'trial_end_at' => $trialEnd,
				'plan' => 'trial',
				'status' => 'active',
			], [ '%s', '%s', '%s', '%s', '%s', '%s' ]);
		}

		return new WP_REST_Response([
			'ok' => true,
			'shop' => $shop,
			'trialEndsOn' => $trialEnd,
		], 200);
	}
}

