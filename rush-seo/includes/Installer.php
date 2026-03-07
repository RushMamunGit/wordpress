<?php
namespace RushSEO;

use WP_Error;

if (!defined('ABSPATH')) {
	exit;
}

class Installer {
	public static function activate(): void
	{
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$prefix = $wpdb->prefix;

		$tables = [];

		$tables[] = "CREATE TABLE {$prefix}rush_seo_shops (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			shop_domain VARCHAR(255) NOT NULL,
			access_token_enc LONGTEXT NULL,
			installed_at DATETIME NULL,
			trial_end_at DATETIME NULL,
			plan VARCHAR(50) DEFAULT 'trial',
			status VARCHAR(50) DEFAULT 'active',
			PRIMARY KEY  (id),
			UNIQUE KEY shop_domain (shop_domain)
		) $charset_collate;";

		$tables[] = "CREATE TABLE {$prefix}rush_seo_shop_settings (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			shop_domain VARCHAR(255) NOT NULL,
			preferences LONGTEXT NULL,
			PRIMARY KEY  (id),
			KEY shop_domain (shop_domain)
		) $charset_collate;";

		$tables[] = "CREATE TABLE {$prefix}rush_seo_scans (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			shop_domain VARCHAR(255) NOT NULL,
			target_type VARCHAR(50) NOT NULL,
			target_ref VARCHAR(255) NULL,
			started_at DATETIME NULL,
			finished_at DATETIME NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'queued',
			score INT NULL,
			PRIMARY KEY  (id),
			KEY shop_domain (shop_domain),
			KEY target_type (target_type),
			KEY status (status)
		) $charset_collate;";

		$tables[] = "CREATE TABLE {$prefix}rush_seo_issues (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			scan_id BIGINT UNSIGNED NOT NULL,
			issue_code VARCHAR(80) NOT NULL,
			severity VARCHAR(20) NOT NULL,
			location VARCHAR(255) NULL,
			details LONGTEXT NULL,
			resolved_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY scan_id (scan_id),
			KEY issue_code (issue_code)
		) $charset_collate;";

		$tables[] = "CREATE TABLE {$prefix}rush_seo_ai_suggestions (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			shop_domain VARCHAR(255) NOT NULL,
			target_ref VARCHAR(255) NULL,
			type VARCHAR(50) NOT NULL,
			prompt_key VARCHAR(255) NULL,
			suggestion LONGTEXT NULL,
			cost_ms INT NULL,
			tokens INT NULL,
			accepted_at DATETIME NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY shop_domain (shop_domain),
			KEY type (type)
		) $charset_collate;";

		$tables[] = "CREATE TABLE {$prefix}rush_seo_redirects (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			shop_domain VARCHAR(255) NOT NULL,
			from_path VARCHAR(2048) NOT NULL,
			to_url VARCHAR(2048) NOT NULL,
			type VARCHAR(10) NOT NULL DEFAULT '301',
			synced TINYINT(1) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY shop_domain (shop_domain),
			KEY from_path (from_path(255))
		) $charset_collate;";

		$tables[] = "CREATE TABLE {$prefix}rush_seo_verifications (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			shop_domain VARCHAR(255) NOT NULL,
			provider VARCHAR(50) NOT NULL,
			code VARCHAR(255) NOT NULL,
			active TINYINT(1) NOT NULL DEFAULT 1,
			PRIMARY KEY  (id),
			KEY shop_domain (shop_domain),
			KEY provider (provider)
		) $charset_collate;";

		$tables[] = "CREATE TABLE {$prefix}rush_seo_schema_configs (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			shop_domain VARCHAR(255) NOT NULL,
			enabled_types LONGTEXT NULL,
			dedupe_rules LONGTEXT NULL,
			PRIMARY KEY  (id),
			KEY shop_domain (shop_domain)
		) $charset_collate;";

		$tables[] = "CREATE TABLE {$prefix}rush_seo_images (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			shop_domain VARCHAR(255) NOT NULL,
			product_id BIGINT NULL,
			image_id BIGINT NULL,
			current_alt VARCHAR(2048) NULL,
			last_checked_at DATETIME NULL,
			auto_updated TINYINT(1) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY shop_domain (shop_domain),
			KEY product_id (product_id)
		) $charset_collate;";

		$tables[] = "CREATE TABLE {$prefix}rush_seo_reports (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			shop_domain VARCHAR(255) NOT NULL,
			week_start DATE NOT NULL,
			overview LONGTEXT NULL,
			email_sent_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY shop_domain (shop_domain),
			KEY week_start (week_start)
		) $charset_collate;";

		$tables[] = "CREATE TABLE {$prefix}rush_seo_jobs (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			shop_domain VARCHAR(255) NULL,
			type VARCHAR(100) NOT NULL,
			payload LONGTEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			attempts INT UNSIGNED NOT NULL DEFAULT 0,
			scheduled_for DATETIME NULL,
			last_error LONGTEXT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY scheduled_for (scheduled_for),
			KEY shop_domain (shop_domain)
		) $charset_collate;";

		foreach ($tables as $sql) {
			dbDelta($sql);
		}

		// Schedule background worker if not yet scheduled
		if (!wp_next_scheduled('rush_seo_process_jobs')) {
			wp_schedule_event(time() + 60, 'five_minutes', 'rush_seo_process_jobs');
		}
	}

	public static function deactivate(): void
	{
		// Unschedule background worker
		wp_clear_scheduled_hook('rush_seo_process_jobs');
	}
}

