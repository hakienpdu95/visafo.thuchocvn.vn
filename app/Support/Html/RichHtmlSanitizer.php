<?php

namespace App\Support\Html;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Làm sạch HTML từ Jodit (preset có chèn ảnh) theo allowlist bằng DOM — dùng cho nội dung soạn trong admin
 * nhưng hiển thị trên trang CÔNG KHAI (VD "Vai trò trong chuỗi cung ứng" ở trang truy xuất).
 * Khác sanitize_rich_text() (strip_tags + regex, không cho <img>): giữ ảnh, lọc thuộc tính/URL/style theo allowlist.
 * Gọi khi lưu và gọi lại khi render (phòng dữ liệu cũ / sửa thẳng DB).
 */
class RichHtmlSanitizer
{
    /** tag => thuộc tính được giữ (ngoài 'style' đã lọc riêng) */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'b' => [], 'strong' => [], 'i' => [], 'em' => [], 'u' => [], 's' => [], 'sub' => [], 'sup' => [],
        'span' => [], 'div' => [], 'blockquote' => [], 'hr' => [],
        'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
        'ul' => [], 'ol' => [], 'li' => [],
        'table' => ['border', 'cellpadding', 'cellspacing'], 'thead' => [], 'tbody' => [], 'tr' => [],
        'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
        'a' => ['href', 'title', 'target'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'data-media-uuid'],
        'figure' => [], 'figcaption' => [],
    ];

    /** Tag bị xóa cả nội dung bên trong (không chỉ gỡ vỏ). */
    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'template', 'noscript', 'link', 'meta'];

    /** Thuộc tính CSS an toàn cho căn chỉnh / kích thước ảnh & chữ (Jodit ghi vào style). */
    private const ALLOWED_CSS = ['text-align', 'font-weight', 'font-style', 'text-decoration', 'color', 'background-color', 'font-size',
        'width', 'height', 'max-width', 'float', 'display', 'vertical-align', 'margin', 'margin-left', 'margin-right', 'margin-top', 'margin-bottom',
        'padding', 'border', 'border-collapse'];

    public static function clean(?string $html): ?string
    {
        if (blank($html)) {
            return null;
        }

        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('__root');
        if ($root === null) {
            return null;
        }

        self::walk($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }
        $out = trim($out);

        // Rỗng thực sự (Jodit để lại "<p><br></p>") → null
        return trim(strip_tags($out, '<img>')) === '' ? null : $out;
    }

    private static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);

                continue;
            }
            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $node->removeChild($child);

                continue;
            }

            self::walk($child);

            if (! array_key_exists($tag, self::ALLOWED)) {
                // Tag lạ (font, jodit, section...) → gỡ vỏ, giữ nội dung
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            self::cleanAttributes($child, $tag);

            if ($tag === 'img' && ! $child->hasAttribute('src')) {
                $node->removeChild($child);
            }
        }
    }

    private static function cleanAttributes(DOMElement $el, string $tag): void
    {
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);
            $value = trim($attr->value);

            if ($name === 'style') {
                $style = self::cleanStyle($value);
                $style === '' ? $el->removeAttribute('style') : $el->setAttribute('style', $style);

                continue;
            }

            if (! in_array($name, self::ALLOWED[$tag], true)) {
                $el->removeAttribute($attr->name);

                continue;
            }

            if (in_array($name, ['href', 'src'], true) && ! self::safeUrl($value, $name === 'src')) {
                $el->removeAttribute($attr->name);
            }
        }

        if ($tag === 'a' && $el->getAttribute('target') === '_blank') {
            $el->setAttribute('rel', 'noopener noreferrer');
        } elseif ($tag === 'a') {
            $el->removeAttribute('target');
        }
    }

    /** http(s), đường dẫn tương đối, mailto/tel (chỉ href). Chặn javascript:, data:, vbscript:... */
    private static function safeUrl(string $url, bool $isImage): bool
    {
        $url = preg_replace('/[\x00-\x20]+/', '', $url);
        if ($url === '' || str_starts_with($url, '//')) {
            return false;
        }
        if (preg_match('#^(https?:)?/#i', $url) || ! preg_match('/^[a-z][a-z0-9+.-]*:/i', $url)) {
            return true;
        }

        return ! $isImage && preg_match('/^(mailto|tel):/i', $url) === 1;
    }

    private static function cleanStyle(string $style): string
    {
        $kept = [];
        foreach (explode(';', $style) as $decl) {
            [$prop, $val] = array_map('trim', explode(':', $decl, 2) + [1 => '']);
            $prop = strtolower($prop);
            if ($prop === '' || $val === '' || ! in_array($prop, self::ALLOWED_CSS, true)) {
                continue;
            }
            // Không cho url(), expression(), @import, escape \ — chỉ giá trị đơn giản
            if (preg_match('/url\s*\(|expression|javascript|[\\\\<>@]/i', $val)) {
                continue;
            }
            $kept[] = $prop . ': ' . $val;
        }

        return implode('; ', $kept);
    }
}
