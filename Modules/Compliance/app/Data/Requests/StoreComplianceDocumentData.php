<?php

namespace Modules\Compliance\Data\Requests;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Spatie\LaravelData\Attributes\Validation\AfterOrEqual;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\File;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Mimes;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreComplianceDocumentData extends Data
{
    public function __construct(
        #[Required, Exists('document_master_types', 'id')]
        public readonly string $document_master_type_id,

        #[Nullable, StringType, Max(150)]
        public readonly ?string $document_number,

        #[Nullable, Date]
        public readonly ?string $issue_date,

        #[Nullable, Date, AfterOrEqual('issue_date')]
        public readonly ?string $expiration_date,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $issued_by,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $notes,

        #[Nullable, StringType, Max(10)]
        public readonly ?string $classification_grade,

        #[Nullable, File, Mimes(['pdf', 'jpg', 'jpeg', 'png']), Max(10240)]
        public readonly ?UploadedFile $file,

        #[Nullable, File, Mimes(['pdf', 'jpg', 'jpeg', 'png']), Max(10240)]
        public readonly ?UploadedFile $pif_file,

        public readonly ComplianceDocumentStatus $status = ComplianceDocumentStatus::Active,
    ) {}

    public static function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ComplianceDocumentStatus::class)],
        ];
    }

    public static function messages(): array
    {
        return [
            'document_master_type_id.required' => 'Vui lòng chọn loại giấy tờ.',
            'document_master_type_id.exists'   => 'Loại giấy tờ không hợp lệ.',

            'document_number.string' => 'Số hiệu không hợp lệ.',
            'document_number.max'    => 'Số hiệu không được vượt quá 150 ký tự.',

            'issue_date.date' => 'Ngày cấp không hợp lệ.',

            'expiration_date.date'           => 'Ngày hết hạn không hợp lệ.',
            'expiration_date.after_or_equal' => 'Ngày hết hạn phải sau hoặc bằng ngày cấp.',

            'issued_by.string' => 'Nơi cấp không hợp lệ.',
            'issued_by.max'    => 'Nơi cấp không được vượt quá 255 ký tự.',

            'notes.string' => 'Ghi chú không hợp lệ.',
            'notes.max'    => 'Ghi chú không được vượt quá 500 ký tự.',

            'file.file'  => 'Tệp tải lên không hợp lệ.',
            'file.mimes' => 'Chỉ chấp nhận file PDF, JPG hoặc PNG.',
            'file.max'   => 'Dung lượng file không được vượt quá 10MB.',

            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.enum'     => 'Trạng thái không hợp lệ.',
        ];
    }
}
