<?php
namespace RushSEO\Services;

if (!defined('ABSPATH')) {
	exit;
}

class Jobs {
	public static function enqueue(string $type, array $payload = [], ?string $shopDomain = null, ?string $scheduledFor = null): ?int
	{
		global $wpdb;
		$table = $wpdb->prefix . 'rush_seo_jobs';
		$inserted = $wpdb->insert($table, [
			'shop_domain' => $shopDomain,
			'type' => $type,
			'payload' => wp_json_encode($payload),
			'status' => 'pending',
			'scheduled_for' => $scheduledFor,
		], [ '%s', '%s', '%s', '%s', '%s' ]);
		return $inserted ? (int) $wpdb->insert_id : null;
	}

	public static function process(): void
	{
		global $wpdb;
		$table = $wpdb->prefix . 'rush_seo_jobs';
		$now = current_time('mysql');
		$jobs = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE status = %s AND (scheduled_for IS NULL OR scheduled_for <= %s) ORDER BY id ASC LIMIT 10", 'pending', $now));
		if (empty($jobs)) {
			return;
		}
		foreach ($jobs as $job) {
			try {
				$wpdb->update($table, [ 'status' => 'running' ], [ 'id' => $job->id ], [ '%s' ], [ '%d' ]);
				$payload = json_decode((string) $job->payload, true) ?: [];
				switch ($job->type) {
					case 'scan':
						self::handleScan($job->shop_domain, $payload);
						break;
					case 'weekly_report':
						self::handleWeeklyReport($job->shop_domain);
						break;
					default:
						// No-op
				}
				$wpdb->update($table, [ 'status' => 'done' ], [ 'id' => $job->id ], [ '%s' ], [ '%d' ]);
			} catch (\Throwable $e) {
				$wpdb->update($table, [
					'status' => 'failed',
					'last_error' => $e->getMessage(),
					'attempts' => (int) $job->attempts + 1,
				], [ 'id' => $job->id ], [ '%s', '%s', '%d' ], [ '%d' ]);
			}
		}
	}

	private static function handleScan(?string $shopDomain, array $payload): void
	{
		if (!$shopDomain) { return; }
		Scanner::scanTarget($shopDomain, $payload);
	}

	private static function handleWeeklyReport(?string $shopDomain): void
	{
		// Placeholder: compose and send via wp_mail later.
	}
}

