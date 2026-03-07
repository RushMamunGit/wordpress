<?php
namespace RushSEO;

if (!defined('ABSPATH')) {
	exit;
}

class Plugin {
	public static function init(): void
	{
		// Register REST routes
		add_action('rest_api_init', [Rest\Routes::class, 'register']);

		// Cron schedules for background jobs
		add_filter('cron_schedules', [self::class, 'register_cron_schedules']);

		// Background job processor hook
		add_action('rush_seo_process_jobs', [Services\Jobs::class, 'process']);
	}

	public static function register_cron_schedules(array $schedules): array
	{
		if (!isset($schedules['five_minutes'])) {
			$schedules['five_minutes'] = [
				'interval' => 300,
				'display'  => __('Every Five Minutes', 'rush-seo'),
			];
		}
		return $schedules;
	}
}

