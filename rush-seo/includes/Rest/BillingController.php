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

	public static function createSubscription(WP_REST_Request $request): WP_REST_Response
	{
		$shop = sanitize_text_field((string) $request->get_param('shop'));
		$returnUrl = (string) $request->get_param('returnUrl');
		if (empty($shop) || empty($returnUrl)) {
			return new WP_REST_Response([ 'error' => 'missing_params' ], 400);
		}
		$query = <<<'GQL'
mutation CreateSubscription($name: String!, $trialDays: Int!, $amount: Decimal!, $returnUrl: URL!) {
  appSubscriptionCreate(
    name: $name
    trialDays: $trialDays
    returnUrl: $returnUrl
    lineItems: [{ plan: { appRecurringPricingDetails: { price: { amount: $amount, currencyCode: USD } } } }]
  ) {
    userErrors { field message }
    confirmationUrl
    appSubscription { id status trialEndsOn }
  }
}
GQL;
		$vars = [
			'name' => 'Rush SEO Monthly',
			'trialDays' => 14,
			'amount' => 20.0,
			'returnUrl' => $returnUrl,
		];
		$res = \RushSEO\Services\Shopify::graphql($shop, $query, $vars);
		if (is_wp_error($res)) {
			return new WP_REST_Response([ 'error' => 'graphql_error', 'message' => $res->get_error_message() ], 502);
		}
		$payload = $res['data']['appSubscriptionCreate'] ?? [];
		if (!empty($payload['userErrors'])) {
			return new WP_REST_Response([ 'error' => 'user_errors', 'details' => $payload['userErrors'] ], 400);
		}
		return new WP_REST_Response([ 'ok' => true, 'confirmationUrl' => $payload['confirmationUrl'] ?? null ], 200);
	}
}

