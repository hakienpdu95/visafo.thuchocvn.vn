<?php

namespace Modules\SalesPackage\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreSalesPackageData extends Data
{
    public function __construct(
        #[Required, StringType, Exists('customers', 'id')]
        public readonly string $customer_id,

        #[Required, StringType, Max(255)]
        public readonly string $name,

        #[Nullable, Date]
        public readonly ?string $expected_deadline,

        #[Nullable, StringType, Max(1000)]
        public readonly ?string $notes,

        /** @var string[] */
        public readonly array $document_ids = [],

        /** @var array<int, array{name: string, files: array<int, \Illuminate\Http\UploadedFile>}> */
        public readonly array $custom_documents = [],
    ) {}

    public static function rules(): array
    {
        return [
            'document_ids'   => ['required_without:custom_documents', 'array'],
            'document_ids.*' => ['string', 'exists:compliance_documents,id'],

            'custom_documents'          => ['required_without:document_ids', 'array'],
            'custom_documents.*.name'   => ['required_with:custom_documents', 'string', 'max:255'],
            'custom_documents.*.files'      => ['required_with:custom_documents', 'array', 'min:1'],
            'custom_documents.*.files.*'    => ['file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public static function messages(): array
    {
        return [
            'customer_id.required' => 'Vui lòng chọn khách hàng.',
            'customer_id.exists'   => 'Khách hàng không hợp lệ.',

            'name.required' => 'Vui lòng đặt tên gói chào hàng.',
            'name.max'      => 'Tên gói không được vượt quá 255 ký tự.',

            'expected_deadline.date' => 'Hạn nộp dự kiến không đúng định dạng ngày.',

            'document_ids.required_without' => 'Vui lòng chọn ít nhất 1 tài liệu để đóng gói.',
            'document_ids.array'            => 'Danh sách tài liệu không hợp lệ.',
            'document_ids.*.exists'         => 'Có tài liệu không hợp lệ trong danh sách đã chọn.',

            'custom_documents.required_without' => 'Vui lòng chọn ít nhất 1 tài liệu để đóng gói.',
            'custom_documents.*.name.required_with'  => 'Vui lòng nhập tên cho tài liệu bổ sung.',
            'custom_documents.*.files.required_with' => 'Vui lòng chọn ít nhất 1 file cho tài liệu bổ sung.',
            'custom_documents.*.files.*.mimes'       => 'File phải là định dạng PDF, DOC, DOCX, XLS, XLSX, JPG hoặc PNG.',
            'custom_documents.*.files.*.max'         => 'Mỗi file không được vượt quá 10MB.',
        ];
    }
}
