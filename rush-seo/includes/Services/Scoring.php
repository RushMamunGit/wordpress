<?php
namespace RushSEO\Services;

if (!defined('ABSPATH')) {
	exit;
}

class Scoring {
	public static function score(array $metrics): int
	{
		$score = 100;
		if (!empty($metrics['title_length']) && ($metrics['title_length'] < 10 || $metrics['title_length'] > 60)) {
			$score -= 10;
		}
		if (!empty($metrics['meta_length']) && ($metrics['meta_length'] < 50 || $metrics['meta_length'] > 160)) {
			$score -= 10;
		}
		if (empty($metrics['has_h1'])) {
			$score -= 10;
		}
		if (!empty($metrics['missing_alts'])) {
			$score -= min(20, (int) $metrics['missing_alts'] * 2);
		}
		if (!empty($metrics['readability_penalty'])) {
			$score -= min(20, (int) $metrics['readability_penalty']);
		}
		if (!empty($metrics['keyword_density']) && ($metrics['keyword_density'] < 0.5 || $metrics['keyword_density'] > 3.0)) {
			$score -= 10;
		}
		$score = max(0, min(100, $score));
		return (int) $score;
	}
}

