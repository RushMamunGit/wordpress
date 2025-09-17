<?php
namespace RushSEO\Rest;

use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
	exit;
}

class EditorController {
	public static function analyze(WP_REST_Request $request): WP_REST_Response
	{
		$text = (string) $request->get_param('text');
		$focus = sanitize_text_field((string) $request->get_param('focus_keyword'));
		if ($text === '') {
			return new WP_REST_Response([ 'error' => 'missing_text' ], 400);
		}
		$annotations = self::findIssues($text, $focus);
		return new WP_REST_Response([ 'ok' => true, 'annotations' => $annotations ], 200);
	}

	private static function findIssues(string $text, string $focus): array
	{
		$annotations = [];
		$lower = mb_strtolower($text);
		$focusLower = mb_strtolower($focus);

		// Critical: missing focus keyword
		if ($focusLower !== '' && mb_strpos($lower, $focusLower) === false) {
			$annotations[] = [
				'severity' => 'red',
				'range' => [ 'start' => 0, 'end' => 0 ],
				'message' => 'Focus keyword missing in content.',
				'suggestion' => 'Add the focus keyword naturally in the first paragraph.',
			];
		}

		// Keyword density
		if ($focusLower !== '') {
			$words = preg_split('/\s+/u', $lower, -1, PREG_SPLIT_NO_EMPTY);
			$total = max(1, count($words));
			$count = 0;
			foreach ($words as $w) {
				if ($w === $focusLower) { $count++; }
			}
			$density = ($count / $total) * 100.0;
			if ($density > 3.0) {
				$annotations[] = [
					'severity' => 'red',
					'range' => [ 'start' => 0, 'end' => 0 ],
					'message' => sprintf('Keyword density too high (%.1f%%).', $density),
					'suggestion' => 'Reduce repetitions and use synonyms.',
				];
			} elseif ($density < 0.5) {
				$annotations[] = [
					'severity' => 'yellow',
					'range' => [ 'start' => 0, 'end' => 0 ],
					'message' => sprintf('Keyword density low (%.1f%%).', $density),
					'suggestion' => 'Include the focus keyword where relevant.',
				];
			}
		}

		// Long sentences
		$offset = 0;
		$sentences = preg_split('/(?<=[.!?])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
		foreach ($sentences as $sentence) {
			$len = mb_strlen($sentence);
			if ($len > 160) {
				$start = mb_strpos($text, $sentence, $offset);
				$end = $start + $len;
				$annotations[] = [
					'severity' => 'yellow',
					'range' => [ 'start' => $start, 'end' => $end ],
					'message' => 'Long sentence. Consider splitting for readability.',
					'suggestion' => 'Split into shorter sentences (12–20 words).',
				];
				$offset = $end;
			}
		}

		return $annotations;
	}
}

