<?php

namespace Modules\LabelTemplate\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use InvalidArgumentException;
use Modules\LabelTemplate\Support\LabelTemplatePreview;
use Throwable;

/**
 * Render mẫu tem bằng dữ liệu giả (stdClass/array) — không truy vấn DB thật.
 */
class PreviewLabelTemplateHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LabelTemplatePreview
    {
        /** @var PreviewLabelTemplateQuery $query */
        $template = $query->labelTemplate;

        $item = (object) [
            'product'    => (object) ['name' => 'Cải xanh'],
            'salesOrder' => (object) [
                'customer_name'    => 'Khách hàng Demo',
                'delivery_address' => '123 Đường ABC, Quận XYZ, TP HCM',
            ],
        ];

        $log = (object) [
            'weight_per_label' => 5.5,
            'mfg_date'         => today(),
            'exp_date'         => today()->addDays(7),
            'supplier_name'    => 'Nhà cung cấp Test',
            'trace_code'       => 'a1b2c3d4e5',
        ];

        $attributes = collect([
            (object) ['attribute_key' => 'HDSD', 'attribute_value' => 'Test dữ liệu HDSD'],
            (object) ['attribute_key' => 'Liều dùng', 'attribute_value' => 'Test Liều dùng'],
        ]);

        // QR mẫu để xem trước bố cục (template có thể bỏ qua nếu không dùng).
        $qrSvg = preg_replace('/^<\?xml.*?\?>\s*/s', '', (new Writer(new ImageRenderer(new RendererStyle(200, 0), new SvgImageBackEnd())))
            ->writeString(url('/')));

        // @include của master bọc lỗi thiếu view thành ViewException → kiểm tra trước để báo đúng nguyên nhân.
        if (! view()->exists($template->view_path)) {
            return LabelTemplatePreview::failed(
                'Không tìm thấy view',
                'Không tìm thấy view "' . $template->view_path . '". Hãy kiểm tra lại View Path hoặc tạo file resources/views/'
                    . str_replace('.', '/', $template->view_path) . '.blade.php.'
            );
        }

        try {
            // render() ngay tại đây để bắt cả lỗi cú pháp/biến thiếu trong template.
            // Mẫu tem là partial → nhúng qua master layout in tem, giống hệt luồng in thật.
            $entry = (object) [
                'viewPath'   => $template->view_path,
                'item'       => $item,
                'log'        => $log,
                'order'      => null,
                'attributes' => $attributes,
                'qrSvg'      => $qrSvg,
                'size'       => $template->default_size,
            ];

            return LabelTemplatePreview::rendered(
                view('labels.master_print', ['items' => [$entry]])->render()
            );
        } catch (InvalidArgumentException) {
            // Laravel ném InvalidArgumentException("View [...] not found.") khi view không tồn tại.
            return LabelTemplatePreview::failed(
                'Không tìm thấy view',
                'Không tìm thấy view "' . $template->view_path . '". Hãy kiểm tra lại View Path hoặc tạo file resources/views/'
                    . str_replace('.', '/', $template->view_path) . '.blade.php.'
            );
        } catch (Throwable $e) {
            return LabelTemplatePreview::failed('Lỗi khi hiển thị mẫu tem', $e->getMessage());
        }
    }
}
