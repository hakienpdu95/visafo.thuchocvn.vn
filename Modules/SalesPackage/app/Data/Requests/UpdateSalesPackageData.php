<?php

namespace Modules\SalesPackage\Data\Requests;

use Illuminate\Validation\Rule;
use Modules\SalesPackage\Enums\SalesPackageStatus;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateSalesPackageData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $name,

        #[Nullable, Date]
        public readonly ?string $expected_deadline,

        #[Nullable, StringType, Max(1000)]
        public readonly ?string $notes,

        public readonly ?string $status,

        /** @var string[] */
        public readonly array $document_ids = [],

        /** @var array<int, array{name: string, file: \Illuminate\Http\UploadedFile}> */
        public readonly array $custom_documents = [],
    ) {}

    public static function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(SalesPackageStatus::class)],

            'document_ids'   => ['nullable', 'array'],
            'document_ids.*' => ['string', 'exists:compliance_documents,id'],

            'custom_documents'        => ['nullable', 'array'],
            'custom_documents.*.name' => ['required_with:custom_documents', 'string', 'max:255'],
            'custom_documents.*.file' => ['required_with:custom_documents', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public static function messages(): array
    {
        return [
            'name.required' => 'Vui lòng đặt tên gói chào hàng.',
            'name.max'      => 'Tên gói không được vượt quá 255 ký tự.',

            'expected_deadline.date' => 'Hạn nộp dự kiến không đúng định dạng ngày.',

            'status.enum' => 'Trạng thái không hợp lệ.',

            'document_ids.*.exists' => 'Có tài liệu không hợp lệ trong danh sách đã chọn.',

            'custom_documents.*.name.required_with' => 'Vui lòng nhập tên cho tài liệu bổ sung.',
            'custom_documents.*.file.required_with' => 'Vui lòng chọn file cho tài liệu bổ sung.',
            'custom_documents.*.file.mimes'          => 'File phải là định dạng PDF, DOC, DOCX, XLS, XLSX, JPG hoặc PNG.',
            'custom_documents.*.file.max'            => 'File không được vượt quá 10MB.',
        ];
    }
}
