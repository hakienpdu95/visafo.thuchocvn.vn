<?php

namespace Modules\Warehouse\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeGenerator
{
    public function toSvg(string $data, int $size = 200): string
    {
        $renderer = new ImageRenderer(new RendererStyle($size), new SvgImageBackEnd());
        $writer   = new Writer($renderer);

        return $writer->writeString($data);
    }
}
