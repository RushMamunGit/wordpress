<?php
namespace RushSEO\Services;

if (!defined('ABSPATH')) {
	exit;
}

class Scanner {
	public static function scanTarget(string $shopDomain, array $payload): void
	{
		$type = $payload['target_type'] ?? 'product';
		$ref = $payload['target_ref'] ?? null;
		if ($ref === null) {
			return;
		}
		$data = Shopify::getResource($shopDomain, $type, (string) $ref);
		$metrics = self::computeMetrics($type, $data);
		$score = Scoring::score($metrics);

		global $wpdb;
		$scans = $wpdb->prefix . 'rush_seo_scans';
		$issuesTable = $wpdb->prefix . 'rush_seo_issues';
		$wpdb->insert($scans, [
			'shop_domain' => $shopDomain,
			'target_type' => $type,
			'target_ref' => (string) $ref,
			'started_at' => current_time('mysql'),
			'finished_at' => current_time('mysql'),
			'status' => 'done',
			'score' => $score,
		], [ '%s', '%s', '%s', '%s', '%s', '%s', '%d' ]);
		$scanId = (int) $wpdb->insert_id;

		foreach (self::issuesFromMetrics($metrics) as $issue) {
			$wpdb->insert($issuesTable, [
				'scan_id' => $scanId,
				'issue_code' => $issue['code'],
				'severity' => $issue['severity'],
				'location' => $issue['location'] ?? null,
				'details' => wp_json_encode($issue['details'] ?? []),
			], [ '%d', '%s', '%s', '%s', '%s' ]);
		}
	}

	private static function computeMetrics(string $type, array $data): array
	{
		$title = (string) ($data['title'] ?? '');
		$body = (string) ($data['body_html'] ?? $data['body'] ?? '');
		$images = (array) ($data['images'] ?? []);
		$missingAlts = 0;
		foreach ($images as $img) {
			$alt = (string) ($img['alt'] ?? '');
			if ($alt === '') { $missingAlts++; }
		}
		$hasH1 = (bool) preg_match('/<h1[^>]*>/i', $body);
		$meta = (string) ($data['metafields_global_description_tag'] ?? $data['metafields']['global']['description_tag'] ?? '');
		$words = preg_split('/\s+/u', wp_strip_all_tags($body), -1, PREG_SPLIT_NO_EMPTY);
		$keywordDensity = 0.0; // computed later if focus present via settings

		return [
			'title_length' => mb_strlen($title),
			'meta_length' => mb_strlen($meta),
			'has_h1' => $hasH1,
			'missing_alts' => $missingAlts,
			'keyword_density' => $keywordDensity,
			'readability_penalty' => self::readabilityPenalty($words),
		];
	}

	private static function readabilityPenalty(array $words): int
	{
		$count = count($words);
		if ($count < 200) { return 0; }
		return min(20, (int) (($count - 200) / 50));
	}

	private static function issuesFromMetrics(array $m): array
	{
		$issues = [];
		if ($m['title_length'] < 10 || $m['title_length'] > 60) {
			$issues[] = [ 'code' => 'title_length', 'severity' => 'medium', 'details' => [ 'title_length' => $m['title_length'] ] ];
		}
		if ($m['meta_length'] < 50 || $m['meta_length'] > 160) {
			$issues[] = [ 'code' => 'meta_length', 'severity' => 'medium', 'details' => [ 'meta_length' => $m['meta_length'] ] ];
		}
		if (!$m['has_h1']) {
			$issues[] = [ 'code' => 'missing_h1', 'severity' => 'high' ];
		}
		if ($m['missing_alts'] > 0) {
			$issues[] = [ 'code' => 'missing_alts', 'severity' => 'high', 'details' => [ 'count' => $m['missing_alts'] ] ];
		}
		return $issues;
	}
}

