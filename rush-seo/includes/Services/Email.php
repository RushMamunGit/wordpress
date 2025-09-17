<?php
namespace RushSEO\Services;

if (!defined('ABSPATH')) {
	exit;
}

class Email {
	public static function sendWeeklyReport(string $shopDomain, array $overview): bool
	{
		$to = get_option('admin_email');
		$subject = sprintf('[Rush SEO] Weekly report for %s', $shopDomain);
		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
		$body = '<h2>Rush SEO Weekly Report</h2>';
		$body .= '<p>Shop: ' . esc_html($shopDomain) . '</p>';
		$body .= '<pre>' . esc_html(wp_json_encode($overview, JSON_PRETTY_PRINT)) . '</pre>';
		return wp_mail($to, $subject, $body, $headers);
	}
}

