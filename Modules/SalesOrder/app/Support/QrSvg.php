<?php

namespace Modules\SalesOrder\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrSvg
{
    /** Trả về markup <svg> inline (không kèm khai báo <?xml ?>) để nhúng thẳng vào HTML. */
    public static function make(string $text, int $size = 200): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle($size, 0), new SvgImageBackEnd())))
            ->writeString($text);

        return preg_replace('/^<\?xml.*?\?>\s*/s', '', $svg);
    }
}
