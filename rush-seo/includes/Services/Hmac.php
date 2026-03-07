<?php
namespace RushSEO\Services;

if (!defined('ABSPATH')) {
	exit;
}

class Hmac {
	public static function verifyFromQuery(array $params, string $sharedSecret): bool
	{
		if (!isset($params['hmac'])) {
			return false;
		}
		$received = $params['hmac'];
		unset($params['hmac'], $params['signature']);
		ksort($params);
		$pairs = [];
		foreach ($params as $key => $value) {
			$pairs[] = $key . '=' . $value;
		}
		$message = implode('&', $pairs);
		$calculated = hash_hmac('sha256', $message, $sharedSecret);
		return hash_equals($calculated, $received);
	}

	public static function verifyWebhook(string $data, string $hmacHeader, string $sharedSecret): bool
	{
		$calculated = base64_encode(hash_hmac('sha256', $data, $sharedSecret, true));
		return hash_equals($calculated, $hmacHeader);
	}
}

