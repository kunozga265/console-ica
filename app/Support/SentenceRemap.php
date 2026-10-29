<?php

namespace App\Support;

/**
 * Maps sentence-span numbers between two renderings of the same sermon body,
 * by matching each sentence's text in order. Used when a rendering change
 * shifts numbering, so saved highlights/bookmarks keep pointing at the same line.
 */
class SentenceRemap
{
    /** @return array<int, string> span id => the sentence's own plain text (excluding nested sentences), in order */
    public static function sentences(string $rendered): array
    {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div>' . $rendered . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $own = function (\DOMNode $node) use (&$own): string {
            $text = '';
            foreach ($node->childNodes as $child) {
                if ($child instanceof \DOMElement && $child->tagName === 'span' && $child->hasAttribute('id')) {
                    continue; // a nested sentence
                }
                $text .= $child instanceof \DOMText ? $child->textContent : $own($child);
            }

            return $text;
        };

        $out = [];
        foreach ((new \DOMXPath($doc))->query('//span[@id and @class="data"]') as $span) {
            $out[(int) $span->getAttribute('id')] = trim(preg_replace('/\s+/u', ' ', $own($span)));
        }

        return $out;
    }

    /**
     * @return array<int, int> old id => new id (only ids that change or merge)
     *
     * Exact text match first, in order. When a rendering change merges
     * sentences (e.g. a split that no longer happens at an HTML entity), an old
     * sentence maps to the new one containing it (letters/digits compared).
     */
    public static function map(string $oldRendered, string $newRendered): array
    {
        $old = self::sentences($oldRendered);
        $newAll = self::sentences($newRendered);
        $new = array_map(null, array_keys($newAll), array_values($newAll));
        // Letters/digits only; an entity the old split cut off its ";" ("&rdquo") is dropped.
        $key = fn (string $t) => preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower(preg_replace('/&#?[a-z0-9]+(?![a-z0-9;])/i', '', $t)));
        $newKeys = array_map(fn ($n) => $key($n[1]), $new);
        $map = [];
        $j = 0;

        foreach ($old as $oldId => $text) {
            if ($text === '') {
                continue;
            }
            $found = null;
            for ($k = $j; $k < count($new); $k++) {
                if ($new[$k][1] === $text) {
                    $found = $k;
                    break;
                }
            }
            if ($found === null && ($needle = $key($text)) !== '') {
                for ($k = $j; $k < count($new); $k++) {
                    if (str_contains($newKeys[$k], $needle)) {
                        $found = $k;
                        break;
                    }
                }
            }
            if ($found !== null) {
                $map[$oldId] = $new[$found][0];
                $j = $found; // merged sentences land on the same line
            }
        }

        return array_filter($map, fn ($newId, $oldId) => $newId !== $oldId, ARRAY_FILTER_USE_BOTH);
    }
}
