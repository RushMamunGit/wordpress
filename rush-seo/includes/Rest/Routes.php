<?php
namespace RushSEO\Rest;

use WP_REST_Server;

if (!defined('ABSPATH')) {
	exit;
}

class Routes {
	public static function register(): void
	{
		register_rest_route('rush-seo/v1', '/oauth/install', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [OAuthController::class, 'install'],
			'permission_callback' => '__return_true',
		]);

		register_rest_route('rush-seo/v1', '/oauth/callback', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [OAuthController::class, 'callback'],
			'permission_callback' => '__return_true',
		]);

		register_rest_route('rush-seo/v1', '/billing/activate', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [BillingController::class, 'activate'],
			'permission_callback' => '__return_true',
		]);

		register_rest_route('rush-seo/v1', '/scan', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ScanController::class, 'enqueue'],
			'permission_callback' => '__return_true',
		]);

		register_rest_route('rush-seo/v1', '/webhooks', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [WebhookController::class, 'handle'],
			'permission_callback' => '__return_true',
		]);

		register_rest_route('rush-seo/v1', '/ai/suggest', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [AiController::class, 'suggest'],
			'permission_callback' => '__return_true',
		]);
	}
}

