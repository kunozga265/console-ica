<?php

namespace App\Support;

/**
 * Prepares a stored sermon body for the sentence renderer
 * (HelperController::generateHighlightLinks).
 */
class SermonBody
{
    /**
     * The WYSIWYG editor saves list items as <li><p>text</p></li>. The renderer
     * wraps each <li> in a sentence span, so the block <p> inside it showed as
     * an empty line before every item (mobile app) and used up a sentence
     * number. Unwrap the item's paragraph, and mark a nested list the way the
     * old editor wrote it ("\r\n\t<ul>") so the parent sentence closes first.
     */
    public static function normalizeLists(?string $html): string
    {
        return (string) preg_replace_callback(
            '/<li>\s*<p(?:\s[^>]*)?>(.*?)<\/p>\s*(<(?:ul|ol)>)?/is',
            fn ($m) => '<li>' . $m[1] . (isset($m[2]) && $m[2] !== '' ? "\r\n\t" . $m[2] : ''),
            (string) $html
        );
    }

    /**
     * For previews (e.g. the home page excerpt): fill-in-the-blank words must not
     * give away the answer before the reveal, so they become empty gaps; after
     * it they read as ordinary text.
     */
    public static function concealFills(?string $html, bool $revealed): string
    {
        return (string) preg_replace_callback(
            '#<mark\b[^>]*\bdata-fill=["\'][^"\']*["\'][^>]*>(.*?)</mark>#is',
            fn ($m) => $revealed
                ? $m[1]
                : '<span class="fill-gap" aria-label="blank">' . str_repeat('&nbsp;', max(4, min(24, mb_strlen(strip_tags($m[1]))))) . '</span>',
            (string) $html
        );
    }
}
