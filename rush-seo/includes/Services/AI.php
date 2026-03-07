<?php
namespace RushSEO\Services;

use WP_Error;

if (!defined('ABSPATH')) {
	exit;
}

class AI {
	private static function getApiKey(): string
	{
		return defined('RUSH_SEO_GEMINI_API_KEY') ? constant('RUSH_SEO_GEMINI_API_KEY') : '';
	}

	public static function suggestTitle(string $context, array $constraints = [])
	{
		return self::generate("Generate an SEO-friendly product/page title under 60 characters.", $context, $constraints);
	}

	public static function suggestMetaDescription(string $context, array $constraints = [])
	{
		return self::generate("Generate an engaging meta description under 155 characters.", $context, $constraints);
	}

	public static function suggestAlt(string $context, array $constraints = [])
	{
		return self::generate("Write a concise, descriptive image alt text under 120 characters.", $context, $constraints);
	}

	private static function generate(string $instruction, string $context, array $constraints = [])
	{
		$apiKey = self::getApiKey();
		if (empty($apiKey)) {
			return new WP_Error('missing_api_key', 'Gemini API key not configured.');
		}

		$prompt = trim($instruction . "\nContext:\n" . $context . "\nConstraints:\n" . wp_json_encode($constraints));
		$endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . rawurlencode($apiKey);
		$args = [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'timeout' => 20,
			'body' => wp_json_encode([
				'contents' => [[ 'parts' => [[ 'text' => $prompt ]] ]],
			]),
		];
		$response = wp_remote_post($endpoint, $args);
		if (is_wp_error($response)) {
			return $response;
		}
		$status = wp_remote_retrieve_response_code($response);
		$body = json_decode(wp_remote_retrieve_body($response), true);
		if ($status !== 200) {
			return new WP_Error('ai_failed', 'Gemini generation failed.', [ 'status' => $status, 'body' => $body ]);
		}
		// Extract plain text from response structure
		$text = '';
		if (!empty($body['candidates'][0]['content']['parts'][0]['text'])) {
			$text = (string) $body['candidates'][0]['content']['parts'][0]['text'];
		}
		return wp_strip_all_tags($text);
	}
}

