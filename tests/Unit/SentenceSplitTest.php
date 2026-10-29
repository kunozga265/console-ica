<?php

namespace Tests\Unit;

use App\Http\Controllers\Web\HelperController;
use App\Support\SentenceRemap;
use App\Support\SermonBody;
use PHPUnit\Framework\TestCase;

/** The sermon sentence renderer (highlight/bookmark numbering). */
class SentenceSplitTest extends TestCase
{
    private const BODY = '<p>He said &ldquo;Go&rdquo; and went. The Death &amp; Resurrection matter; we believe.</p>';

    public function test_html_entities_are_not_treated_as_sentence_ends(): void
    {
        $html = (new HelperController())->generateHighlightLinks(self::BODY);

        $this->assertStringContainsString('He said &ldquo;Go&rdquo; and went</a>', $html);
        $this->assertStringContainsString('The Death &amp; Resurrection matter</a></span>; ', $html); // a real ";" still splits
        $this->assertSame(3, substr_count($html, "class='data'><a"));
    }

    public function test_saved_lines_follow_merged_sentences(): void
    {
        $legacy = new HelperController();
        $legacy->legacyEntitySplit = true;

        $map = SentenceRemap::map($legacy->generateHighlightLinks(self::BODY), (new HelperController())->generateHighlightLinks(self::BODY));

        // old 1 "He said “Go" + old 2 "and went" → new 1; old 3+4 → new 2; old 5 → new 3
        $this->assertSame([2 => 1, 3 => 2, 4 => 2, 5 => 3], $map);
    }

    public function test_editor_list_paragraphs_do_not_create_empty_sentences(): void
    {
        $html = (new HelperController())->generateHighlightLinks(
            SermonBody::normalizeLists('<ol><li><p>One</p></li><li><p>Two</p><ol><li><p>Two a</p></li></ol></li></ol>')
        );

        $this->assertStringNotContainsString("class='data'><p>", $html);
        $this->assertSame(3, substr_count($html, "class='data'><a"));
    }
}
