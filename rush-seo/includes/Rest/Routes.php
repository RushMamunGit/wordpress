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

		// Admin UI shell
		register_rest_route('rush-seo/v1', '/app', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [\RushSEO\App\AppController::class, 'index'],
			'permission_callback' => '__return_true',
		]);

		// Content and editor analysis
		register_rest_route('rush-seo/v1', '/content', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ContentController::class, 'get'],
			'permission_callback' => '__return_true',
		]);
		register_rest_route('rush-seo/v1', '/content', [
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => [ContentController::class, 'update'],
			'permission_callback' => '__return_true',
		]);
		register_rest_route('rush-seo/v1', '/editor/analyze', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [EditorController::class, 'analyze'],
			'permission_callback' => '__return_true',
		]);

		// Theme integration
		register_rest_route('rush-seo/v1', '/theme/verification', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ThemeController::class, 'verification'],
			'permission_callback' => '__return_true',
		]);
		register_rest_route('rush-seo/v1', '/theme/schema', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ThemeController::class, 'schema'],
			'permission_callback' => '__return_true',
		]);

		// Billing create subscription
		register_rest_route('rush-seo/v1', '/billing/create', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [BillingController::class, 'createSubscription'],
			'permission_callback' => '__return_true',
		]);
	}
}

