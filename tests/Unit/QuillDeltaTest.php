<?php

namespace Tests\Unit;

use App\Support\QuillDelta;
use PHPUnit\Framework\TestCase;

/** Notes: Quill Delta (mobile app, stored) ⇄ HTML (website editor). */
class QuillDeltaTest extends TestCase
{
    private function roundTrip(array $ops): array
    {
        return QuillDelta::compact(json_decode(QuillDelta::fromHtml(QuillDelta::toHtml(json_encode($ops))), true));
    }

    public function test_inline_formatting_and_colours_survive_a_round_trip(): void
    {
        $ops = [
            ['insert' => 'Grace', 'attributes' => ['bold' => true]],
            ['insert' => ' and '],
            ['insert' => 'truth', 'attributes' => ['italic' => true, 'underline' => true]],
            ['insert' => ' see ', 'attributes' => ['color' => '#FFE91E63', 'background' => '#FFFDD835']],
            ['insert' => 'John 1', 'attributes' => ['link' => 'https://bible.com/bible/59/JHN.1.ESV']],
            ['insert' => "\n"],
        ];

        $html = QuillDelta::toHtml(json_encode($ops));
        $this->assertStringContainsString('<strong>Grace</strong>', $html);
        $this->assertStringContainsString('color: #E91E63; background-color: #FDD835', $html);
        $this->assertSame(QuillDelta::compact($ops), $this->roundTrip($ops));
    }

    public function test_flat_quill_lists_become_nested_html_lists_and_back(): void
    {
        $ops = [
            ['insert' => 'Points'],
            ['insert' => "\n", 'attributes' => ['header' => 1]],
            ['insert' => 'One'],
            ['insert' => "\n", 'attributes' => ['list' => 'ordered']],
            ['insert' => 'One a'],
            ['insert' => "\n", 'attributes' => ['indent' => 1, 'list' => 'bullet']],
            ['insert' => 'One a i'],
            ['insert' => "\n", 'attributes' => ['indent' => 2, 'list' => 'bullet']],
            ['insert' => 'Two'],
            ['insert' => "\n", 'attributes' => ['list' => 'ordered']],
            ['insert' => 'Switch to bullets'],
            ['insert' => "\n", 'attributes' => ['list' => 'bullet']],
            ['insert' => 'After'],
            ['insert' => "\n"],
        ];

        $html = QuillDelta::toHtml(json_encode($ops));
        $this->assertStringStartsWith('<h1>Points</h1><ol><li><p>One</p><ul><li><p>One a</p><ul><li><p>One a i</p>', $html);
        $this->assertSame(QuillDelta::compact($ops), $this->roundTrip($ops));
    }

    public function test_indents_without_an_html_equivalent_use_data_indent(): void
    {
        $ops = [
            ['insert' => 'Intro'],
            ['insert' => "\n"],
            ['insert' => 'Starts indented'],
            ['insert' => "\n", 'attributes' => ['indent' => 1, 'list' => 'bullet']],
            ['insert' => 'Indented paragraph'],
            ['insert' => "\n", 'attributes' => ['indent' => 2]],
        ];

        $html = QuillDelta::toHtml(json_encode($ops));
        $this->assertStringContainsString('<ul data-indent="1">', $html);
        $this->assertStringContainsString('<p data-indent="2">', $html);
        $this->assertSame(QuillDelta::compact($ops), $this->roundTrip($ops));
    }

    public function test_html_from_the_web_editor_becomes_a_valid_delta(): void
    {
        // What Tiptap emits: <li><p>, rgb() colours, a trailing empty paragraph after a list.
        $html = '<h2>Notes</h2><p><strong><em>Key</em></strong> <span style="color: rgb(233, 30, 99)">idea</span></p>'
            . '<ul><li><p>First</p><ol><li><p>Sub</p></li></ol></li></ul><p></p>';

        $ops = json_decode(QuillDelta::fromHtml($html), true);

        $this->assertSame([
            ['insert' => 'Notes'],
            ['insert' => "\n", 'attributes' => ['header' => 2]],
            ['insert' => 'Key', 'attributes' => ['bold' => true, 'italic' => true]],
            ['insert' => ' '],
            ['insert' => 'idea', 'attributes' => ['color' => '#FFE91E63']],
            ['insert' => "\nFirst"], // neighbouring ops with the same attributes merge, as in Quill
            ['insert' => "\n", 'attributes' => ['list' => 'bullet']],
            ['insert' => 'Sub'],
            ['insert' => "\n", 'attributes' => ['indent' => 1, 'list' => 'ordered']],
        ], $ops);
        // Quill requires the document to end with a newline.
        $this->assertStringEndsWith("\n", end($ops)['insert']);
    }

    public function test_edge_cases(): void
    {
        $this->assertSame('[{"insert":"\n"}]', QuillDelta::fromHtml(''));
        $this->assertSame('[{"insert":"\n"}]', QuillDelta::fromHtml('<p></p>'));
        // Already a Delta: stored as-is.
        $delta = '[{"insert":"Hi\n"}]';
        $this->assertSame($delta, QuillDelta::fromHtml($delta));
        // Plain text / HTML bodies are shown as they are.
        $this->assertSame('<p>web note</p>', QuillDelta::toHtml('<p>web note</p>'));
        // Text is escaped; links keep their URL.
        $this->assertSame('<p>a &lt;b&gt; &amp; c</p>', QuillDelta::toHtml('[{"insert":"a <b> & c\n"}]'));
        $this->assertSame('a <b> & c', QuillDelta::toText('[{"insert":"a <b> & c\n"}]'));
    }
}
