<?php

namespace App\Support;

/**
 * Converts sermon notes between the mobile app's editor (flutter_quill — Quill
 * Delta JSON, which is what the `notes` table stores) and the website's
 * WYSIWYG editor (Tiptap — HTML).
 *
 * Delta model: a list of `insert` ops. Inline formatting lives on text
 * (bold, italic, underline, strike, link, color, background); block
 * formatting lives on the "\n" ending each line (header, list, indent,
 * blockquote, code-block). Lists are flat lines with an `indent` level.
 * Colours are Flutter ARGB hex ("#FFE91E63").
 *
 * HTML model: what Tiptap reads and writes — <p>, <h1-3>, nested <ul>/<ol>
 * with <li><p>, <blockquote>, <pre><code>, and <strong>/<em>/<u>/<s>/<a>/
 * <span style="color; background-color">. Indents with no HTML equivalent
 * (a list that starts indented, an indented paragraph) travel as
 * data-indent, kept by the editor's Indent extension.
 */
class QuillDelta
{
    private const BLOCK_KEYS = ['header', 'list', 'indent', 'blockquote', 'code-block', 'align', 'direction'];

    /* ===================== Delta → HTML ===================== */

    public static function toHtml(string|array|null $delta): string
    {
        $ops = self::ops($delta);
        if ($ops === null) {
            return (string) $delta; // already HTML (or plain text)
        }

        $html = '';
        $lists = []; // stack of open lists: ['tag' => ul|ol, 'level' => int]
        $closeListsTo = function (int $level) use (&$lists, &$html) {
            while ($lists && end($lists)['level'] > $level) {
                $html .= '</li></' . array_pop($lists)['tag'] . '>';
            }
        };
        $prevBlock = null; // 'blockquote' | 'code' while one is open

        foreach (self::lines($ops) as [$runs, $block]) {
            $inline = self::runsToHtml($runs);
            $list = $block['list'] ?? null;
            $indent = (int) ($block['indent'] ?? 0);

            $kind = isset($block['code-block']) ? 'code' : (isset($block['blockquote']) ? 'blockquote' : null);
            if ($prevBlock && $prevBlock !== $kind) {
                $html .= $prevBlock === 'code' ? '</code></pre>' : '</blockquote>';
                $prevBlock = null;
            }

            if ($list) {
                $tag = $list === 'ordered' ? 'ol' : 'ul';
                if (! $lists) {
                    // A list may start indented in the app; keep that as data-indent.
                    $html .= "<{$tag}" . ($indent ? " data-indent=\"{$indent}\"" : '') . '><li>';
                    $lists[] = ['tag' => $tag, 'level' => $indent];
                } else {
                    $base = $lists[0]['level'];
                    $level = max($indent, $base);
                    $top = end($lists)['level'];
                    if ($level > $top) {
                        // Deeper: open a nested list inside the current item (one level at a time).
                        for ($l = $top + 1; $l <= $level; $l++) {
                            $html .= "<{$tag}><li>";
                            $lists[] = ['tag' => $tag, 'level' => $l];
                        }
                    } else {
                        $closeListsTo($level);
                        if (end($lists)['tag'] !== $tag) {
                            // Bullet ↔ numbered at the same level: a separate list.
                            $html .= '</li></' . array_pop($lists)['tag'] . '>';
                            if ($lists) {
                                $html .= "<{$tag}><li>";
                            } else {
                                $html .= "<{$tag}" . ($level ? " data-indent=\"{$level}\"" : '') . '><li>';
                            }
                            $lists[] = ['tag' => $tag, 'level' => $level];
                        } else {
                            $html .= '</li><li>';
                        }
                    }
                }
                $html .= '<p>' . $inline . '</p>';
                continue;
            }

            $closeListsTo(-1);

            if ($kind === 'code') {
                $html .= $prevBlock === 'code' ? "\n" : '<pre><code>';
                $html .= htmlspecialchars(implode('', array_column($runs, 0)), ENT_NOQUOTES);
                $prevBlock = 'code';
                continue;
            }
            if ($kind === 'blockquote' && $prevBlock !== 'blockquote') {
                $html .= '<blockquote>';
                $prevBlock = 'blockquote';
            }

            $level = (int) ($block['header'] ?? 0);
            $tag = $level >= 1 && $level <= 3 ? "h{$level}" : 'p';
            $attr = $indent ? " data-indent=\"{$indent}\"" : '';
            $html .= "<{$tag}{$attr}>{$inline}</{$tag}>";
        }

        $closeListsTo(-1);
        if ($prevBlock) {
            $html .= $prevBlock === 'code' ? '</code></pre>' : '</blockquote>';
        }

        return $html;
    }

    /** Splits ops into lines: [[ [text, inlineAttrs], ... ], blockAttrs]. */
    private static function lines(array $ops): array
    {
        $lines = [];
        $runs = [];
        foreach ($ops as $op) {
            $insert = $op['insert'] ?? '';
            if (! is_string($insert)) {
                continue; // embeds (images etc.) aren't used in notes
            }
            $attrs = is_array($op['attributes'] ?? null) ? $op['attributes'] : [];
            $inline = array_diff_key($attrs, array_flip(self::BLOCK_KEYS));
            $block = array_intersect_key($attrs, array_flip(self::BLOCK_KEYS));

            // Windows line endings (text pasted into the app) — "\r" is invisible but
            // would come back from the web editor as a line break.
            $parts = explode("\n", str_replace("\r", '', $insert));
            foreach ($parts as $i => $text) {
                if ($text !== '') {
                    $runs[] = [$text, $inline];
                }
                if ($i < count($parts) - 1) {
                    $lines[] = [$runs, $block];
                    $runs = [];
                }
            }
        }
        if ($runs) {
            $lines[] = [$runs, []]; // text after the last newline
        }

        return $lines;
    }

    private static function runsToHtml(array $runs): string
    {
        $html = '';
        foreach ($runs as [$text, $a]) {
            $s = htmlspecialchars($text, ENT_NOQUOTES);
            $style = [];
            if (! empty($a['color']) && ($c = self::cssColor($a['color']))) {
                $style[] = "color: {$c}";
            }
            if (! empty($a['background']) && ($c = self::cssColor($a['background']))) {
                $style[] = "background-color: {$c}";
            }
            if ($style) {
                $s = '<span style="' . implode('; ', $style) . '">' . $s . '</span>';
            }
            if (! empty($a['strike'])) {
                $s = "<s>{$s}</s>";
            }
            if (! empty($a['underline'])) {
                $s = "<u>{$s}</u>";
            }
            if (! empty($a['italic'])) {
                $s = "<em>{$s}</em>";
            }
            if (! empty($a['bold'])) {
                $s = "<strong>{$s}</strong>";
            }
            if (! empty($a['link'])) {
                $s = '<a href="' . htmlspecialchars($a['link'], ENT_QUOTES) . '" target="_blank" rel="noopener noreferrer nofollow">' . $s . '</a>';
            }
            $html .= $s;
        }

        return $html;
    }

    /* ===================== HTML → Delta ===================== */

    /** @return string Delta JSON, as the mobile app stores it */
    public static function fromHtml(?string $html): string
    {
        $html = trim((string) $html);
        if ($html !== '' && self::ops($html) !== null) {
            return $html; // already a Delta
        }

        // The web editor always adds an empty paragraph after a trailing list or
        // quote (so the cursor can leave it); that isn't part of the note.
        $html = preg_replace('#(</(?:ul|ol|blockquote)>)\s*<p(?:\s[^>]*)?>(?:\s|<br\s*/?>)*</p>\s*$#i', '$1', $html);

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="root">' . $html . '</div>', LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $ops = [];
        $root = $doc->getElementById('root');
        if ($root) {
            self::blocks($root, $ops, []);
        }
        if (! $ops) {
            $ops[] = ['insert' => "\n"];
        }

        return json_encode(self::compact($ops), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** Walks block-level children, appending ops; $list = ['type', 'level'] inside lists. */
    private static function blocks(\DOMNode $parent, array &$ops, array $context): void
    {
        $inline = []; // loose inline content (e.g. text directly in <li> or root)
        $flush = function (array $block) use (&$inline, &$ops) {
            if ($inline) {
                foreach ($inline as $op) {
                    $ops[] = $op;
                }
                $inline = [];
                $ops[] = self::newline($block);
            }
        };

        foreach ($parent->childNodes as $node) {
            $tag = $node instanceof \DOMElement ? strtolower($node->tagName) : null;

            if (in_array($tag, ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div'], true)) {
                $flush(self::lineBlock($context));
                $block = self::lineBlock($context);
                if ($tag[0] === 'h' && $tag !== 'div') {
                    $block['header'] = min(3, (int) substr($tag, 1));
                    unset($block['list']);
                }
                if (($i = (int) $node->getAttribute('data-indent')) > 0 && empty($context['list'])) {
                    $block['indent'] = $i;
                }
                self::lineOps($node, $ops, $block);
            } elseif ($tag === 'ul' || $tag === 'ol') {
                $flush(self::lineBlock($context));
                $type = $tag === 'ol' ? 'ordered' : 'bullet';
                $level = isset($context['level']) ? $context['level'] + 1 : (int) $node->getAttribute('data-indent');
                foreach ($node->childNodes as $li) {
                    if ($li instanceof \DOMElement && strtolower($li->tagName) === 'li') {
                        self::blocks($li, $ops, ['list' => $type, 'level' => $level] + $context);
                    }
                }
            } elseif ($tag === 'blockquote') {
                $flush(self::lineBlock($context));
                self::blocks($node, $ops, ['blockquote' => true] + $context);
            } elseif ($tag === 'pre') {
                $flush(self::lineBlock($context));
                foreach (explode("\n", rtrim($node->textContent, "\n")) as $line) {
                    if ($line !== '') {
                        $ops[] = ['insert' => $line];
                    }
                    $ops[] = ['insert' => "\n", 'attributes' => ['code-block' => true]];
                }
            } elseif ($tag === 'br') {
                $flush(self::lineBlock($context));
            } elseif ($node instanceof \DOMText && trim($node->textContent) === '' && ! $inline) {
                continue; // formatting whitespace between blocks
            } else {
                self::inlineOps($node, $inline, []);
            }
        }

        $flush(self::lineBlock($context));
    }

    /** Block attributes for a line in the given context. */
    private static function lineBlock(array $context): array
    {
        $block = [];
        if (! empty($context['list'])) {
            $block['list'] = $context['list'];
            if (! empty($context['level'])) {
                $block['indent'] = $context['level'];
            }
        }
        if (! empty($context['blockquote'])) {
            $block['blockquote'] = true;
        }

        return $block;
    }

    /** One block element → its inline ops + a terminating newline (a <br> starts a new line with the same block attrs). */
    private static function lineOps(\DOMNode $node, array &$ops, array $block): void
    {
        $line = [];
        self::inlineOps($node, $line, []);
        if ($line && end($line) === null) {
            array_pop($line); // a <br> at the end of a block doesn't start a new line
        }
        foreach ($line as $op) {
            if ($op === null) { // <br>
                $ops[] = self::newline($block);
                continue;
            }
            $ops[] = $op;
        }
        $ops[] = self::newline($block);
    }

    private static function inlineOps(\DOMNode $node, array &$out, array $attrs): void
    {
        if ($node instanceof \DOMText) {
            $text = $node->textContent;
            if ($text !== '') {
                $out[] = $attrs ? ['insert' => $text, 'attributes' => $attrs] : ['insert' => $text];
            }

            return;
        }
        if (! $node instanceof \DOMElement) {
            return;
        }

        switch (strtolower($node->tagName)) {
            case 'strong': case 'b': $attrs['bold'] = true; break;
            case 'em': case 'i': $attrs['italic'] = true; break;
            case 'u': $attrs['underline'] = true; break;
            case 's': case 'strike': case 'del': $attrs['strike'] = true; break;
            case 'a':
                if ($node->getAttribute('href') !== '') {
                    $attrs['link'] = $node->getAttribute('href');
                }
                break;
            case 'mark': $attrs['background'] = self::argb($node->getAttribute('data-color') ?: '#FFFDD835') ?? '#FFFDD835'; break;
            case 'br': $out[] = null; return;
        }

        if ($style = $node->getAttribute('style')) {
            if (preg_match('/(?:^|;)\s*color\s*:\s*([^;]+)/i', $style, $m) && ($c = self::argb($m[1]))) {
                $attrs['color'] = $c;
            }
            if (preg_match('/background(?:-color)?\s*:\s*([^;]+)/i', $style, $m) && ($c = self::argb($m[1]))) {
                $attrs['background'] = $c;
            }
        }

        foreach ($node->childNodes as $child) {
            self::inlineOps($child, $out, $attrs);
        }
    }

    private static function newline(array $block): array
    {
        return $block ? ['insert' => "\n", 'attributes' => $block] : ['insert' => "\n"];
    }

    /** Merges neighbouring ops with identical attributes, as Quill does. */
    public static function compact(array $ops): array
    {
        $out = [];
        foreach ($ops as $op) {
            if ($op === null || ! is_string($op['insert'] ?? null) || $op['insert'] === '') {
                continue;
            }
            $attrs = $op['attributes'] ?? [];
            ksort($attrs);
            $last = count($out) - 1;
            if ($last >= 0 && ($out[$last]['attributes'] ?? []) == $attrs) {
                $out[$last]['insert'] .= $op['insert'];
                continue;
            }
            $out[] = $attrs ? ['insert' => $op['insert'], 'attributes' => $attrs] : ['insert' => $op['insert']];
        }

        return $out;
    }

    /* ===================== helpers ===================== */

    /** Decoded Delta ops, or null when the value isn't a Delta. */
    public static function ops(string|array|null $delta): ?array
    {
        if (is_array($delta)) {
            return $delta;
        }
        $s = ltrim((string) $delta);
        if ($s === '' || $s[0] !== '[') {
            return null;
        }
        $ops = json_decode($s, true);

        return is_array($ops) && array_is_list($ops) && (! $ops || isset($ops[0]['insert'])) ? $ops : null;
    }

    /** Plain text of a note (either format), for previews. */
    public static function toText(?string $body): string
    {
        $ops = self::ops($body);
        $text = $ops !== null
            ? implode('', array_map(fn ($op) => is_string($op['insert'] ?? null) ? $op['insert'] : '', $ops))
            : html_entity_decode(strip_tags(preg_replace('#</(p|h\d|li|blockquote)>#i', "\n", (string) $body)));

        return trim(preg_replace("/\n{3,}/", "\n\n", $text));
    }

    /** Flutter ARGB "#FFRRGGBB" → CSS (#RRGGBB, or rgba() when translucent). */
    private static function cssColor(string $c): ?string
    {
        $c = trim($c);
        if (preg_match('/^#([0-9a-f]{2})([0-9a-f]{6})$/i', $c, $m)) {
            $alpha = hexdec($m[1]);
            if ($alpha === 255) {
                return '#' . strtoupper($m[2]);
            }
            [$r, $g, $b] = array_map('hexdec', str_split($m[2], 2));

            return sprintf('rgba(%d, %d, %d, %s)', $r, $g, $b, round($alpha / 255, 3));
        }

        return preg_match('/^#[0-9a-f]{6}$/i', $c) ? strtoupper($c) : null;
    }

    /** CSS colour (#RGB, #RRGGBB, rgb(), rgba()) → Flutter ARGB "#FFRRGGBB". */
    private static function argb(string $css): ?string
    {
        $css = strtolower(trim($css));
        if (preg_match('/^#([0-9a-f]{3})$/', $css, $m)) {
            $css = '#' . preg_replace('/(.)/', '$1$1', $m[1]);
        }
        if (preg_match('/^#([0-9a-f]{6})$/', $css, $m)) {
            return '#FF' . strtoupper($m[1]);
        }
        if (preg_match('/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(?:,\s*([\d.]+)\s*)?\)$/', $css, $m)) {
            $a = isset($m[4]) && $m[4] !== '' ? (int) round(min(1, (float) $m[4]) * 255) : 255;

            return sprintf('#%02X%02X%02X%02X', $a, $m[1], $m[2], $m[3]);
        }

        return null;
    }
}
