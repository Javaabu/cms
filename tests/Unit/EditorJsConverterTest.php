<?php

namespace Javaabu\Cms\Tests\Unit;

use Javaabu\BlockEditor\Document;
use Javaabu\Cms\Tests\TestCase;
use Javaabu\Cms\Upgrade\EditorJsConverter;
use PHPUnit\Framework\Attributes\Test;

class EditorJsConverterTest extends TestCase
{
    #[Test]
    public function it_converts_editor_js_blocks_to_a_block_editor_document(): void
    {
        $converter = new EditorJsConverter;

        $document = $converter->convert(json_encode(['blocks' => [
            ['type' => 'header', 'data' => ['text' => 'ސުރުޚީ', 'level' => 2]],
            ['type' => 'paragraph', 'data' => ['text' => 'ދިވެހި <b>ބޯ</b> <a href="https://javaabu.com">ލިންކު</a><br>ފަހު']],
            ['type' => 'list', 'data' => ['style' => 'ordered', 'items' => ['އެކެއް', ['content' => 'ދޭއް', 'items' => ['ތިނެއް']]]]],
            ['type' => 'quote', 'data' => ['text' => 'ބުނުން', 'caption' => 'މީހާ']],
            ['type' => 'delimiter', 'data' => []],
            ['type' => 'table', 'data' => ['withHeadings' => true, 'content' => [['ނަން', 'އަގު'], ['ފޮތް', '10']]]],
            ['type' => 'raw', 'data' => ['html' => '<div>HTML</div>']],
            ['type' => 'embed', 'data' => ['embed' => 'https://www.youtube.com/embed/x', 'caption' => 'ވީޑިއޯ']],
            ['type' => 'image', 'data' => ['file' => ['url' => 'https://example.com/a.jpg'], 'caption' => 'ފޮޓޯ']],
            ['type' => 'columns', 'data' => ['cols' => [['blocks' => [['type' => 'paragraph', 'data' => ['text' => 'ކޮލަމް']]]]]]],
            ['type' => 'actionCard', 'data' => ['title' => 'x']],
            ['type' => 'paragraph', 'data' => ['text' => '']],
        ]]));

        $types = array_column($document['content'], 'type');

        $this->assertSame('doc', $document['type']);
        $this->assertSame(['heading', 'paragraph', 'orderedList', 'blockquote', 'horizontalRule', 'table', 'html', 'embed', 'image', 'paragraph'], $types);
        $this->assertSame(2, $document['content'][0]['attrs']['level']);

        $paragraph = $document['content'][1]['content'];

        $this->assertSame('ދިވެހި ', $paragraph[0]['text']);
        $this->assertSame([['type' => 'bold']], $paragraph[1]['marks']);
        $this->assertSame('link', $paragraph[3]['marks'][0]['type']);
        $this->assertSame('https://javaabu.com', $paragraph[3]['marks'][0]['attrs']['href']);
        $this->assertSame('hardBreak', $paragraph[4]['type']);

        // The nested list stays nested, and ordered
        $this->assertSame('orderedList', $document['content'][2]['content'][1]['content'][1]['type']);
        $this->assertSame('tableHeader', $document['content'][5]['content'][0]['content'][0]['type']);
        $this->assertSame('tableCell', $document['content'][5]['content'][1]['content'][0]['type']);
        $this->assertSame('https://example.com/a.jpg', $document['content'][8]['attrs']['src']);
        $this->assertSame(['actionCard' => 1], $converter->skipped());

        $html = Document::make($document)->toHtml();

        $this->assertStringContainsString('ސުރުޚީ', $html);
        $this->assertStringContainsString('<strong>ބޯ</strong>', $html);
    }

    #[Test]
    public function plain_html_becomes_an_html_block_and_empty_content_null(): void
    {
        $converter = new EditorJsConverter;

        $this->assertSame([['type' => 'html', 'attrs' => ['html' => '<p>ހ</p>']]], $converter->convert('<p>ހ</p>')['content']);
        $this->assertNull($converter->convert(null));
        $this->assertNull($converter->convert(''));
        $this->assertNull($converter->convert('{"blocks": []}'));

        $doc = ['type' => 'doc', 'content' => [['type' => 'paragraph']]];

        $this->assertSame($doc, $converter->convert($doc));
    }
}
