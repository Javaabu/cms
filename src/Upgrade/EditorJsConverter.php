<?php

namespace Javaabu\Cms\Upgrade;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Converts v1's Editor.js content to a javaabu/block-editor document
 * (ProseMirror JSON). Paragraphs, headings, lists, quotes, delimiters,
 * tables, raw HTML, embeds, images and columns are converted; inline bold,
 * italic, underline, strike, code and links become marks. Other blocks
 * (v1's action cards, related posts, documents and statistics) are left out
 * and listed in skipped().
 */
class EditorJsConverter
{
    /** @var array<string, int> block type => how many were left out */
    protected array $skipped = [];

    /**
     * @return array{type: string, content: array<int, array<string, mixed>>}|null null for empty content
     */
    public function convert(mixed $content): ?array
    {
        if ($content === null || $content === '') {
            return null;
        }

        $data = is_string($content) ? json_decode($content, true) : $content;

        // Not Editor.js: v1 also stored plain HTML
        if (! is_array($data)) {
            return is_string($content) && trim($content) !== ''
                ? $this->document([['type' => 'html', 'attrs' => ['html' => $content]]])
                : null;
        }

        // Already a block-editor document
        if (($data['type'] ?? null) === 'doc') {
            return $data;
        }

        $nodes = $this->blocks($data['blocks'] ?? (array_is_list($data) ? $data : []));

        return $nodes === [] ? null : $this->document($nodes);
    }

    /**
     * @return array<string, int>
     */
    public function skipped(): array
    {
        return $this->skipped;
    }

    /**
     * @param  array<int, mixed>  $blocks
     * @return array<int, array<string, mixed>>
     */
    protected function blocks(array $blocks): array
    {
        $nodes = [];

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            foreach ($this->block((string) ($block['type'] ?? ''), (array) ($block['data'] ?? [])) as $node) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    protected function block(string $type, array $data): array
    {
        return match ($type) {
            'paragraph' => $this->paragraphs((string) ($data['text'] ?? '')),
            'header', 'heading' => [$this->heading($data)],
            'list', 'nestedList', 'checklist' => [$this->list($data)],
            'quote' => [$this->quote($data)],
            'delimiter' => [['type' => 'horizontalRule']],
            'table' => [$this->table($data)],
            'raw', 'html' => filled($data['html'] ?? null) ? [['type' => 'html', 'attrs' => ['html' => (string) $data['html']]]] : [],
            'embed' => $this->embed($data),
            'image', 'simpleImage', 'mediaImage', 'MediaImage', 'mediaThumb', 'MediaThumb' => $this->image($data),
            'columns' => $this->columns($data),
            default => $this->skip($type),
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function paragraphs(string $html): array
    {
        $inline = $this->inline($html);

        return $inline === [] ? [] : [['type' => 'paragraph', 'content' => $inline]];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function heading(array $data): array
    {
        $level = max(1, min(6, (int) ($data['level'] ?? 2)));
        $inline = $this->inline((string) ($data['text'] ?? ''));

        return ['type' => 'heading', 'attrs' => ['level' => $level], 'content' => $inline];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function list(array $data): array
    {
        $ordered = ($data['style'] ?? 'unordered') === 'ordered';

        return [
            'type' => $ordered ? 'orderedList' : 'bulletList',
            'content' => array_map(fn ($item) => $this->listItem($item, $data), array_values((array) ($data['items'] ?? []))),
        ];
    }

    /**
     * @param  array<string, mixed>  $list
     * @return array<string, mixed>
     */
    protected function listItem(mixed $item, array $list): array
    {
        $text = is_array($item) ? (string) ($item['content'] ?? $item['text'] ?? '') : (string) $item;
        $content = [['type' => 'paragraph', 'content' => $this->inline($text)]];

        if (is_array($item) && ! empty($item['items'])) {
            $content[] = $this->list([...$list, 'items' => $item['items']]);
        }

        return ['type' => 'listItem', 'content' => $content];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function quote(array $data): array
    {
        $content = $this->paragraphs((string) ($data['text'] ?? ''));

        if (filled($data['caption'] ?? null)) {
            $content = [...$content, ...$this->paragraphs('— ' . $data['caption'])];
        }

        return ['type' => 'blockquote', 'content' => $content ?: [['type' => 'paragraph']]];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function table(array $data): array
    {
        $headings = (bool) ($data['withHeadings'] ?? false);
        $rows = [];

        foreach (array_values((array) ($data['content'] ?? [])) as $index => $row) {
            $cell = $headings && $index === 0 ? 'tableHeader' : 'tableCell';

            $rows[] = [
                'type' => 'tableRow',
                'content' => array_map(fn ($text) => [
                    'type' => $cell,
                    'content' => [['type' => 'paragraph', 'content' => $this->inline((string) $text)]],
                ], array_values((array) $row)),
            ];
        }

        return ['type' => 'table', 'content' => $rows];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    protected function embed(array $data): array
    {
        $src = $data['embed'] ?? $data['source'] ?? null;

        if (! filled($src)) {
            return $this->skip('embed');
        }

        return [['type' => 'embed', 'attrs' => array_filter([
            'src' => (string) $src,
            'title' => filled($data['caption'] ?? null) ? strip_tags((string) $data['caption']) : null,
        ])]];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    protected function image(array $data): array
    {
        $src = $data['url'] ?? $data['file']['url'] ?? $data['src'] ?? null;

        if (! filled($src)) {
            return $this->skip('image');
        }

        return [['type' => 'image', 'attrs' => array_filter([
            'src' => (string) $src,
            'alt' => filled($data['alt'] ?? null) ? (string) $data['alt'] : null,
            'caption' => filled($data['caption'] ?? null) ? strip_tags((string) $data['caption']) : null,
        ])]];
    }

    /**
     * Columns are flattened: their blocks one after another
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    protected function columns(array $data): array
    {
        $nodes = [];

        foreach ((array) ($data['cols'] ?? []) as $column) {
            $nodes = [...$nodes, ...$this->blocks((array) ($column['blocks'] ?? []))];
        }

        return $nodes;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function skip(string $type): array
    {
        $type = $type === '' ? 'unknown' : $type;

        $this->skipped[$type] = ($this->skipped[$type] ?? 0) + 1;

        return [];
    }

    /**
     * Editor.js inline HTML to text nodes with marks
     *
     * @return array<int, array<string, mixed>>
     */
    public function inline(string $html): array
    {
        $html = trim(str_replace('&nbsp;', ' ', $html));

        if ($html === '') {
            return [];
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $document->getElementsByTagName('body')->item(0);

        return $body ? $this->merge($this->walk($body, [])) : [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $marks
     * @return array<int, array<string, mixed>>
     */
    protected function walk(DOMNode $node, array $marks): array
    {
        $nodes = [];

        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMText) {
                if ($child->wholeText !== '') {
                    $nodes[] = array_filter(['type' => 'text', 'text' => $child->wholeText, 'marks' => $marks]);
                }

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            if ($child->tagName === 'br') {
                $nodes[] = ['type' => 'hardBreak'];

                continue;
            }

            $mark = $this->mark($child);

            $nodes = [...$nodes, ...$this->walk($child, $mark ? [...$marks, $mark] : $marks)];
        }

        return $nodes;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function mark(DOMElement $element): ?array
    {
        return match ($element->tagName) {
            'b', 'strong' => ['type' => 'bold'],
            'i', 'em' => ['type' => 'italic'],
            'u' => ['type' => 'underline'],
            's', 'strike', 'del' => ['type' => 'strike'],
            'code' => ['type' => 'code'],
            'a' => $element->getAttribute('href') !== ''
                ? ['type' => 'link', 'attrs' => array_filter([
                    'href' => $element->getAttribute('href'),
                    'target' => $element->getAttribute('target') === '_blank' ? '_blank' : null,
                ])]
                : null,
            default => null,
        };
    }

    /**
     * Join neighbouring text nodes with the same marks
     *
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, array<string, mixed>>
     */
    protected function merge(array $nodes): array
    {
        $merged = [];

        foreach ($nodes as $node) {
            $last = array_key_last($merged);

            if ($last !== null
                && $node['type'] === 'text'
                && $merged[$last]['type'] === 'text'
                && ($merged[$last]['marks'] ?? []) === ($node['marks'] ?? [])) {
                $merged[$last]['text'] .= $node['text'];

                continue;
            }

            $merged[] = $node;
        }

        return $merged;
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array{type: string, content: array<int, array<string, mixed>>}
     */
    protected function document(array $nodes): array
    {
        return ['type' => 'doc', 'content' => $nodes];
    }
}
