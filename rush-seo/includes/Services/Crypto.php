<?php
namespace RushSEO\Services;

if (!defined('ABSPATH')) {
	exit;
}

class Crypto {
	public static function encrypt(string $plaintext): string
	{
		$key = defined('RUSH_SEO_CRYPTO_KEY') ? constant('RUSH_SEO_CRYPTO_KEY') : '';
		if (empty($key) || !function_exists('openssl_encrypt')) {
			return $plaintext; // fallback
		}
		$iv = random_bytes(16);
		$cipher = openssl_encrypt($plaintext, 'AES-256-CBC', hash('sha256', $key, true), OPENSSL_RAW_DATA, $iv);
		return base64_encode($iv . $cipher);
	}

	public static function decrypt(string $ciphertext): string
	{
		$key = defined('RUSH_SEO_CRYPTO_KEY') ? constant('RUSH_SEO_CRYPTO_KEY') : '';
		if (empty($key) || !function_exists('openssl_decrypt')) {
			return $ciphertext; // fallback
		}
		$raw = base64_decode($ciphertext, true);
		if ($raw === false || strlen($raw) < 17) {
			return $ciphertext;
		}
		$iv = substr($raw, 0, 16);
		$cipher = substr($raw, 16);
		$plain = openssl_decrypt($cipher, 'AES-256-CBC', hash('sha256', $key, true), OPENSSL_RAW_DATA, $iv);
		return $plain === false ? $ciphertext : $plain;
	}
}

