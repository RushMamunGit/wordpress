<?php
namespace RushSEO\Services;

if (!defined('ABSPATH')) {
	exit;
}

class Theme {
	public static function installVerificationMeta(string $shop, array $codes): bool
	{
		$snippet = self::buildVerificationSnippet($codes);
		$ok = self::putAsset($shop, 'snippets/rush-seo-verification.liquid', $snippet);
		if (!$ok) { return false; }
		return self::ensureSnippetIncluded($shop, 'rush-seo-verification');
	}

	public static function installSchemaSnippets(string $shop, array $types): bool
	{
		$snippet = self::buildSchemaSnippet($types);
		$ok = self::putAsset($shop, 'snippets/rush-seo-schema.liquid', $snippet);
		if (!$ok) { return false; }
		return self::ensureSnippetIncluded($shop, 'rush-seo-schema');
	}

	private static function putAsset(string $shop, string $key, string $content): bool
	{
		$themeId = self::getMainThemeId($shop);
		if (!$themeId) { return false; }
		$body = [ 'asset' => [ 'key' => $key, 'value' => $content ] ];
		$res = Shopify::rest($shop, 'PUT', "themes/{$themeId}/assets.json", [ 'body' => $body ]);
		return !is_wp_error($res);
	}

	private static function ensureSnippetIncluded(string $shop, string $snippetName): bool
	{
		$themeId = self::getMainThemeId($shop);
		if (!$themeId) { return false; }
		$res = Shopify::rest($shop, 'GET', "themes/{$themeId}/assets.json", [ 'body' => null ]);
		$layoutKey = 'layout/theme.liquid';
		$layout = null;
		if (!is_wp_error($res) && !empty($res['assets'])) {
			foreach ($res['assets'] as $asset) {
				if (($asset['key'] ?? '') === $layoutKey) { $layout = $asset; break; }
			}
		}
		if (!$layout) { return false; }
		$get = Shopify::rest($shop, 'GET', "themes/{$themeId}/assets.json?asset[key]=" . rawurlencode($layoutKey));
		if (is_wp_error($get)) { return false; }
		$content = (string) ($get['asset']['value'] ?? '');
		$includeTag = "{% include '" . $snippetName . "' %}";
		if (strpos($content, $includeTag) === false) {
			$content .= "\n" . $includeTag . "\n";
			$body = [ 'asset' => [ 'key' => $layoutKey, 'value' => $content ] ];
			$put = Shopify::rest($shop, 'PUT', "themes/{$themeId}/assets.json", [ 'body' => $body ]);
			return !is_wp_error($put);
		}
		return true;
	}

	private static function getMainThemeId(string $shop): ?int
	{
		$res = Shopify::rest($shop, 'GET', 'themes.json');
		if (is_wp_error($res) || empty($res['themes'])) { return null; }
		foreach ($res['themes'] as $t) {
			if (($t['role'] ?? '') === 'main') { return (int) $t['id']; }
		}
		return null;
	}

	private static function buildVerificationSnippet(array $codes): string
	{
		$lines = [];
		foreach ($codes as $provider => $code) {
			if (!$code) { continue; }
			$provider = esc_html($provider);
			$code = esc_html($code);
			$lines[] = "<meta name=\"{$provider}-site-verification\" content=\"{$code}\">";
		}
		return implode("\n", $lines) . "\n";
	}

	private static function buildSchemaSnippet(array $types): string
	{
		// Minimal Product JSON-LD example guarded by template type
		$snippet = <<<'LIQ'
{% if template contains 'product' and product %}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": {{ product.title | json }},
  "image": {{ product.images | map: 'src' | json }},
  "description": {{ product.description | strip_html | json }},
  "sku": {{ product.selected_or_first_available_variant.sku | json }},
  "offers": {
    "@type": "Offer",
    "priceCurrency": {{ shop.currency | json }},
    "price": {{ product.selected_or_first_available_variant.price | json }},
    "availability": "https://schema.org/{% if product.available %}InStock{% else %}OutOfStock{% endif %}"
  }
}
</script>
{% endif %}
LIQ;
		return $snippet;
	}
}

