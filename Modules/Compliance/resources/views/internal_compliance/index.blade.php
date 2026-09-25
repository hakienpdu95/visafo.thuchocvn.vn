@extends('layouts.backend')
@section('title', 'Hồ sơ năng lực VISAFO')

@php
    use App\Services\Media\MediaUrlService;
    use Modules\Compliance\Models\InternalFacility;
    use Modules\Product\Enums\DocumentGroupType;

    $mediaUrlService = app(MediaUrlService::class);

    $buildDocRows = function (InternalFacility $facility) use ($mediaUrlService) {
        return $facility->documents->map(function ($d) use ($facility, $mediaUrlService) {
            $media = $d->getMedia('attachments_private')->map(fn ($m) => [
                'id'          => $m->id,
                'name'        => $m->file_name,
                'size'        => $m->size,
                'is_image'    => str_starts_with($m->mime_type, 'image/'),
                'url'         => $mediaUrlService->url($m),
                'delete_url'  => route('backend.internal-facilities.documents.media.destroy', [$facility, $d->id, $m->id]),
            ])->values();

            return [
                'id'                      => $d->id,
                'facility_id'             => $facility->id,
                'facility_name'           => $facility->name,
                'document_master_type_id' => $d->document_master_type_id,
                'document_group'          => $d->documentType->document_group?->value,
                'group_label'             => $d->documentType->document_group?->label() ?? '—',
                'type_name'               => $d->documentType->name,
                'document_number'         => $d->document_number,
                'issued_by'               => $d->issued_by,
                'issue_date'              => $d->issue_date?->format('Y-m-d'),
                'issue_date_display'      => $d->issue_date?->format('d/m/Y'),
                'expiration_date'         => $d->expiration_date?->format('Y-m-d'),
                'expiration_date_display' => $d->expiration_date?->format('d/m/Y'),
                'is_expired'              => $d->isExpired(),
                'is_expiring_soon'        => $d->isExpiringWithinDays(30),
                'status_value'            => $d->status->value,
                'status_label'            => $d->status->label(),
                'status_badge'            => $d->status->badgeClass(),
                'media'                   => $media,
                'media_count'             => $media->count(),
                'update_url'              => route('backend.internal-facilities.documents.update', [$facility, $d->id]),
                'delete_url'              => route('backend.internal-facilities.documents.destroy', [$facility, $d->id]),
            ];
        })->values();
    };

    $allDocRows = $buildDocRows($headquarter);

    $rankRisk = fn ($row) => $row['is_expired'] ? 2 : ($row['is_expiring_soon'] ? 1 : 0);

    $docsByGroup = collect(DocumentGroupType::cases())
        ->mapWithKeys(fn (DocumentGroupType $group) => [
            $group->value => $allDocRows->where('document_group', $group->value)->sortByDesc($rankRisk)->values(),
        ]);

    $hrAggregateTypeNames = ['Giấy xác nhận kiến thức về an toàn thực phẩm', 'Khám sức khỏe định kỳ'];
    $employeeSyncedCodes  = ['personnel_training', 'personnel_periodic_health', 'personnel_health'];

    $requiredTypeIds = collect($documentTypesByGroup)->collapse()
        ->reject(fn ($t) => in_array($t->code, $employeeSyncedCodes, true))
        ->pluck('id');

    $validTypeIds = $allDocRows->where('status_value', 'active')->where('is_expired', false)
        ->pluck('document_master_type_id')->unique();

    $completionTotal = $requiredTypeIds->count() + count($hrAggregateTypeNames);
    $completionDone  = $requiredTypeIds->intersect($validTypeIds)->count()
        + ($employeeStats['attp_valid_pct'] === 100 ? 1 : 0)
        + ($employeeStats['health_valid_pct'] === 100 ? 1 : 0);
    $completionPercent = $completionTotal > 0 ? (int) round($completionDone / $completionTotal * 100) : 0;

    $modalIsEdit     = (bool) old('_document_id');
    $modalFacilityId = old('_target_facility_id', $headquarter->id);

    $allDocumentTypes = collect($documentTypesByGroup)->collapse()
        ->map(fn ($t) => [
            'id'                      => $t->id,
            'name'                    => $t->name,
            'document_group'          => $t->document_group->value,
            'group_label'             => $t->document_group->label(),
            'has_expiration_date'     => (bool) $t->has_expiration_date,
            'has_issue_place'         => (bool) $t->has_issue_place,
            'default_validity_months' => $t->default_validity_months,
        ]);
@endphp

@section('content')
<div x-data="{ tab: '{{ DocumentGroupType::LegalFacility->value }}' }">

    <div class="rounded-md overflow-hidden mb-4" style="background-color:#0F4C3A">
        <div class="p-6 flex flex-wrap items-center justify-between gap-4 text-white">
            <div class="flex items-center gap-4 min-w-0">
                <div class="w-14 h-14 rounded-full bg-white flex items-center justify-center shrink-0">
                    <span class="text-lg font-bold" style="color:#0F4C3A">VF</span>
                </div>
                <div class="min-w-0">
                    <p class="text-lg font-bold leading-tight truncate">Công ty CP Thực phẩm VISAFO</p>
                    <p class="text-sm text-white/70 mt-0.5">MST: 0312345678 · Doanh nghiệp nhỏ</p>
                </div>
            </div>

            <div class="text-right shrink-0">
                <p class="text-3xl font-extrabold leading-none">{{ $completionPercent }}%</p>
                <p class="text-xs text-white/70 mt-1">Hoàn thiện hồ sơ</p>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-3 mb-6">
        <div class="flex items-center gap-2">
            <a href="{{ route('backend.internal-compliance.export') }}" class="btn btn-ghost btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M7 10l5 5 5-5M12 15V3"/></svg>
                Xuất Hồ sơ (ZIP)
            </a>
            @if($canManageDocuments)
            <button type="button" x-show="tab !== 'history'" x-cloak class="btn btn-sm text-white border-0 gap-1.5" style="background-color:#0F4C3A"
                    @click="window.openAddDocumentModal('{{ $headquarter->id }}', tab)">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Tải hồ sơ lên
            </button>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success py-2.5 px-4 mb-5 text-sm rounded-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-error py-2.5 px-4 mb-5 text-sm rounded-sm">{{ session('error') }}</div>
    @endif

    <div class="flex flex-wrap gap-6 border-b border-gray-200 mb-5">
        @foreach(DocumentGroupType::cases() as $group)
        <button type="button" @click="tab = '{{ $group->value }}'"
                class="pb-3 text-sm font-medium border-b-2 -mb-px transition-colors"
                :class="tab === '{{ $group->value }}' ? 'border-green-800 text-green-800 font-semibold' : 'border-transparent text-gray-400 hover:text-gray-600'">
            {{ $group->label() }}
        </button>
        @endforeach
        <button type="button" @click="tab = 'history'"
                class="pb-3 text-sm font-medium border-b-2 -mb-px transition-colors"
                :class="tab === 'history' ? 'border-green-800 text-green-800 font-semibold' : 'border-transparent text-gray-400 hover:text-gray-600'">
            Lịch sử
        </button>
    </div>

    @php
        $documentCard = function (array $doc) use ($canManageDocuments) {
            $statusText  = $doc['is_expired'] ? 'Hết hạn' : ($doc['is_expiring_soon'] ? 'Sắp hết hạn' : 'Hợp lệ');
            $statusClass = $doc['is_expired']
                ? 'bg-red-50 text-red-600 border-red-100'
                : ($doc['is_expiring_soon'] ? 'bg-amber-50 text-amber-600 border-amber-100' : 'bg-green-50 text-green-700 border-green-100');

            return compact('statusText', 'statusClass');
        };
    @endphp

    @foreach(DocumentGroupType::cases() as $group)
    @continue($group === DocumentGroupType::Personnel)
    <div x-show="tab === '{{ $group->value }}'" x-cloak>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($docsByGroup[$group->value] as $doc)
                @php $meta = $documentCard($doc); @endphp
                <div class="bg-white rounded-sm shadow-sm border border-gray-100 p-4 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-11 h-11 rounded-lg bg-green-50 text-green-700 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-sm text-gray-800 truncate">{{ $doc['type_name'] }}</p>
                            <p class="text-xs text-gray-400 truncate">
                                {{ $doc['document_number'] ?: 'Chưa có số hiệu' }}
                                @if($doc['issue_date_display']) · Cấp {{ $doc['issue_date_display'] }} @endif
                            </p>
                            @if($doc['media_count'] > 0)
                            <p class="mt-0.5 inline-flex items-center gap-1 text-[11px] text-gray-400">
                                <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                {{ $doc['media_count'] }} tệp đính kèm
                            </p>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <span class="badge badge-sm border {{ $meta['statusClass'] }}">{{ $meta['statusText'] }}</span>

                        @if($canManageDocuments)
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <button type="button" @click="open = !open" class="btn btn-ghost btn-xs btn-circle">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>
                            </button>
                            <ul x-show="open" x-transition x-cloak @click="open = false"
                                class="absolute right-0 mt-1 menu bg-base-100 rounded-box shadow-lg border border-base-200 w-36 z-20 p-1">
                                <li><button type="button" onclick="window.openViewFilesModal({{ Js::from($doc) }})">Xem danh sách tệp</button></li>
                                <li><button type="button" onclick="window.openEditDocumentModal({{ Js::from($doc) }})">Sửa</button></li>
                                <li><button type="button" class="text-error" onclick="window.internalComplianceDeleteConfirm('{{ $doc['delete_url'] }}', {{ Js::from($doc['type_name']) }})">Xóa</button></li>
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="md:col-span-2 rounded-sm border border-dashed border-gray-200 py-10 text-center text-sm text-gray-400">
                    Chưa có hồ sơ nào trong nhóm {{ $group->label() }}.
                </div>
            @endforelse
        </div>
    </div>
    @endforeach

    <div x-show="tab === '{{ DocumentGroupType::Personnel->value }}'" x-cloak>
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-400">Đồng bộ tự động từ module Nhân sự</p>
            @can('employee.view')
            <a href="{{ route('backend.employees.index') }}" class="btn btn-ghost btn-xs">Đi tới Module Nhân sự →</a>
            @endcan
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
            <div class="bg-white rounded-sm border border-gray-100 shadow-sm p-4">
                <p class="text-xs text-gray-400">Tổng số nhân sự</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($employeeStats['total']) }}</p>
            </div>
            <div class="bg-white rounded-sm border border-gray-100 shadow-sm p-4">
                <p class="text-xs text-gray-400">Tỷ lệ có giấy khám SK còn hạn</p>
                <p class="text-2xl font-bold mt-1 {{ $employeeStats['health_valid_pct'] < 80 ? 'text-red-600' : 'text-green-700' }}">{{ $employeeStats['health_valid_pct'] }}%</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $employeeStats['health_valid'] }}/{{ $employeeStats['total'] }} nhân sự</p>
            </div>
            <div class="bg-white rounded-sm border border-gray-100 shadow-sm p-4">
                <p class="text-xs text-gray-400">Tỷ lệ chứng nhận ATTP còn hạn</p>
                <p class="text-2xl font-bold mt-1 {{ $employeeStats['attp_valid_pct'] < 80 ? 'text-red-600' : 'text-green-700' }}">{{ $employeeStats['attp_valid_pct'] }}%</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $employeeStats['attp_valid'] }}/{{ $employeeStats['total'] }} nhân sự</p>
            </div>
        </div>

        <p class="text-xs font-medium text-gray-500 mb-2">Nhân sự thiếu / sắp hết hạn giấy tờ y tế/ATTP — cần bổ sung</p>
        @if(count($employeeStats['at_risk']))
        <div class="bg-white rounded-sm border border-gray-100 shadow-sm overflow-x-auto mb-6">
            <table class="table table-sm">
                <thead>
                    <tr class="text-xs text-gray-400">
                        <th>Nhân viên</th>
                        <th>Vị trí</th>
                        <th>Khám sức khỏe</th>
                        <th>Chứng nhận ATTP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employeeStats['at_risk'] as $emp)
                    <tr>
                        <td><a href="{{ $emp['edit_url'] }}" class="font-semibold text-sm text-gray-800 hover:text-green-700">{{ $emp['full_name'] }}</a></td>
                        <td class="text-sm text-gray-500">{{ $emp['job_title'] ?: '—' }}</td>
                        <td><span class="badge badge-sm {{ $emp['health_status_badge'] }}">{{ $emp['health_status_label'] }}</span></td>
                        <td><span class="badge badge-sm {{ $emp['attp_status_badge'] }}">{{ $emp['attp_status_label'] }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="rounded-sm bg-green-50 border border-green-100 text-green-700 text-sm py-3 px-4 mb-6">
            Toàn bộ nhân sự đều có đủ giấy tờ còn hiệu lực.
        </div>
        @endif

        @php
            $hrAggregateMap = [
                'Giấy xác nhận kiến thức về an toàn thực phẩm' => [
                    'valid' => $employeeStats['attp_valid'],
                    'total' => $employeeStats['total'],
                ],
                'Khám sức khỏe định kỳ' => [
                    'valid' => $employeeStats['health_valid'],
                    'total' => $employeeStats['total'],
                ],
            ];
        @endphp

        <p class="text-xs font-medium text-gray-500 mb-2">Hồ sơ nhân sự</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($docsByGroup[DocumentGroupType::Personnel->value] as $doc)
                @php $aggregate = $hrAggregateMap[$doc['type_name']] ?? null; @endphp
                <div class="bg-white rounded-sm shadow-sm border border-gray-100 p-4 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-11 h-11 rounded-lg bg-green-50 text-green-700 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-sm text-gray-800 truncate">{{ $doc['type_name'] }}</p>
                            @if($aggregate)
                            <p class="text-xs text-gray-400 truncate">
                                {{ $aggregate['valid'] }}/{{ $aggregate['total'] }} nhân sự
                                @if($aggregate['valid'] < $aggregate['total']) · Thiếu {{ $aggregate['total'] - $aggregate['valid'] }} nhân sự @endif
                            </p>
                            @else
                            <p class="text-xs text-gray-400 truncate">
                                {{ $doc['document_number'] ?: 'Chưa có số hiệu' }}
                                @if($doc['issue_date_display']) · Cấp {{ $doc['issue_date_display'] }} @endif
                            </p>
                            @if($doc['media_count'] > 0)
                            <p class="mt-0.5 inline-flex items-center gap-1 text-[11px] text-gray-400">
                                <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                {{ $doc['media_count'] }} tệp đính kèm
                            </p>
                            @endif
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        @if($aggregate)
                        <span class="badge badge-sm border {{ $aggregate['valid'] === $aggregate['total'] && $aggregate['total'] > 0 ? 'bg-green-50 text-green-700 border-green-100' : 'bg-amber-50 text-amber-600 border-amber-100' }}">
                            {{ $aggregate['valid'] === $aggregate['total'] && $aggregate['total'] > 0 ? 'Hợp lệ' : 'Cần bổ sung' }}
                        </span>
                        @else
                        @php $meta = $documentCard($doc); @endphp
                        <span class="badge badge-sm border {{ $meta['statusClass'] }}">{{ $meta['statusText'] }}</span>
                        @endif

                        @if($canManageDocuments && !$aggregate)
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <button type="button" @click="open = !open" class="btn btn-ghost btn-xs btn-circle">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>
                            </button>
                            <ul x-show="open" x-transition x-cloak @click="open = false"
                                class="absolute right-0 mt-1 menu bg-base-100 rounded-box shadow-lg border border-base-200 w-36 z-20 p-1">
                                <li><button type="button" onclick="window.openViewFilesModal({{ Js::from($doc) }})">Xem danh sách tệp</button></li>
                                <li><button type="button" onclick="window.openEditDocumentModal({{ Js::from($doc) }})">Sửa</button></li>
                                <li><button type="button" class="text-error" onclick="window.internalComplianceDeleteConfirm('{{ $doc['delete_url'] }}', {{ Js::from($doc['type_name']) }})">Xóa</button></li>
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="md:col-span-2 rounded-sm border border-dashed border-gray-200 py-10 text-center text-sm text-gray-400">
                    Chưa có hồ sơ nào trong nhóm Nhân sự.
                </div>
            @endforelse
        </div>
    </div>

    <div x-show="tab === 'history'" x-cloak class="rounded-sm border border-dashed border-gray-200 py-14 text-center text-sm text-gray-400">
        Chưa có dữ liệu lịch sử thay đổi hồ sơ.
    </div>

    <div class="mt-6 rounded-sm bg-green-50 border border-green-100 p-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-green-100 text-green-700 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <p class="font-semibold text-sm text-green-800">Hồ sơ DN đã đủ dữ liệu nền</p>
                <p class="text-xs text-green-700/70">Có thể dùng hồ sơ này để bắt đầu làm việc với Nhà cung cấp đầu vào.</p>
            </div>
        </div>
        <a href="{{ route('backend.vendors.index') }}" class="btn btn-sm text-white border-0 gap-1.5" style="background-color:#0F4C3A">
            Sang NCC đầu vào
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </a>
    </div>

</div>

{{-- ── Modal: Tải lên / Sửa hồ sơ năng lực ─────────────────────────────── --}}
@if($canManageDocuments)
<dialog id="addDocumentModal" class="modal" @if($errors->any()) data-autoopen="1" @endif>
    <div class="modal-box max-w-5xl rounded-md p-3 relative">
        <button type="button" class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3" onclick="addDocumentModal.close()">✕</button>

        <h3 class="font-bold text-lg mb-5" id="documentModalTitle">{{ $modalIsEdit ? 'Sửa hồ sơ' : 'Tải hồ sơ lên' }}</h3>

        @if($errors->any())
        <div class="alert alert-error py-2 px-3 mb-4 text-xs rounded-lg">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST"
              action="{{ $modalIsEdit
                  ? route('backend.internal-facilities.documents.update', [$modalFacilityId, old('_document_id')])
                  : route('backend.internal-facilities.documents.store', $modalFacilityId) }}"
              enctype="multipart/form-data" class="space-y-4"
              data-store-url-template="{{ route('backend.internal-facilities.documents.store', ['internal_facility' => '__FID__']) }}"
              data-update-url-template-raw="{{ route('backend.internal-facilities.documents.update', ['internal_facility' => '__FID__', 'document' => '__ID__']) }}"
              x-data="documentUploadForm({{ Js::from($allDocumentTypes) }}, {{ Js::from((string) old('document_master_type_id', '')) }})">
            @csrf
            <input type="hidden" name="_method" value="{{ $modalIsEdit ? 'PUT' : '' }}">
            <input type="hidden" name="_document_id" value="{{ old('_document_id') }}">
            <input type="hidden" name="_target_facility_id" value="{{ $modalFacilityId }}">


            <div class="grid grid-cols-2 gap-3">
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text text-xs font-medium">Nhóm hồ sơ</span></label>
                    <input type="text" readonly tabindex="-1" :value="selected.group_label ?? ''"
                           class="input input-bordered w-full rounded-sm bg-gray-50 text-gray-500">
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text text-xs font-medium">Tên tài liệu <span class="text-error">*</span></span></label>
                    <select id="ts-document_master_type_id" name="document_master_type_id"
                            class="select select-bordered w-full rounded-sm" data-ts-placeholder="— Chọn loại giấy tờ —"
                            @change="selectedId = $event.target.value">
                        @foreach($allDocumentTypes as $type)
                        <option value="{{ $type['id'] }}" @selected(old('document_master_type_id') === $type['id'])>{{ $type['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="form-control" :class="selected.has_issue_place ? '' : 'col-span-2'">
                    <label class="label py-0 pb-1.5"><span class="label-text text-xs font-medium">Số hiệu</span></label>
                    <input type="text" name="document_number" value="{{ old('document_number') }}" class="input input-bordered w-full rounded-sm">
                </div>
                <div class="form-control" x-show="selected.has_issue_place">
                    <label class="label py-0 pb-1.5"><span class="label-text text-xs font-medium">Nơi cấp</span></label>
                    <input type="text" name="issued_by" value="{{ old('issued_by') }}" class="input input-bordered w-full rounded-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="form-control" :class="selected.has_expiration_date ? '' : 'col-span-2'">
                    <label class="label py-0 pb-1.5"><span class="label-text text-xs font-medium">Ngày cấp</span></label>
                    <input type="text" id="fp-issue_date" name="issue_date" value="{{ old('issue_date') }}"
                           class="input input-bordered w-full rounded-sm" placeholder="dd/mm/yyyy" autocomplete="off">
                </div>
                <div class="form-control" x-show="selected.has_expiration_date">
                    <label class="label py-0 pb-1.5"><span class="label-text text-xs font-medium">Ngày hết hạn</span></label>
                    <input type="text" id="fp-expiration_date" name="expiration_date" value="{{ old('expiration_date') }}"
                           class="input input-bordered w-full rounded-sm" placeholder="dd/mm/yyyy" autocomplete="off">
                </div>
            </div>

            <div class="form-control" x-show="existingMedia.length > 0" x-cloak>
                <label class="label py-0 pb-1.5"><span class="label-text text-xs font-medium">Các tệp đính kèm hiện tại</span></label>
                <template x-for="media in existingMedia" :key="media.id">
                    <div class="flex items-center justify-between p-3 mb-2 bg-gray-50 border border-gray-200 rounded-lg">
                        <div class="flex items-center gap-2 min-w-0">
                            <svg x-show="media.is_image" class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <svg x-show="!media.is_image" class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span class="truncate text-sm text-gray-700" x-text="media.name"></span>
                            <span class="shrink-0 text-xs text-gray-400" x-text="formatFileSize(media.size)"></span>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <a :href="media.url" target="_blank" class="btn btn-ghost btn-xs">Xem</a>
                            <button type="button" class="btn btn-ghost btn-xs text-error" :disabled="removingMediaId === media.id"
                                    @click="removeExistingMedia(media)" title="Xóa file này">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text text-xs font-medium">Tải thêm tệp mới (Sẽ được gộp chung với các tệp hiện tại)</span><span class="label-text-alt text-xs text-base-content/40">PDF, JPG, PNG — tối đa 100MB/file</span></label>
                <input type="file" name="files[]" x-ref="filesInput" multiple
                       accept=".pdf,.jpg,.jpeg,.png" class="file-input file-input-bordered w-full rounded-sm"
                       @change="onFilesChange($event)">

                <ul class="mt-2 space-y-1" x-show="files.length > 0" x-cloak>
                    <template x-for="(file, index) in files" :key="index">
                        <li class="flex items-center justify-between gap-2 rounded-sm border border-gray-200 bg-gray-50 px-2.5 py-1.5">
                            <div class="flex items-center gap-2 min-w-0">
                                <svg x-show="isImageFile(file)" class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <svg x-show="!isImageFile(file)" class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span class="truncate text-xs text-gray-700" x-text="file.name"></span>
                                <span class="shrink-0 text-xs text-base-content/40" x-text="formatFileSize(file.size)"></span>
                            </div>
                            <button type="button" class="btn btn-ghost btn-xs btn-circle shrink-0" @click="removeFile(index)" title="Bỏ chọn file này">✕</button>
                        </li>
                    </template>
                </ul>
            </div>

            <div class="modal-action mt-2 pt-4 border-t border-gray-100">
                <button type="button" class="btn btn-ghost border border-gray-300 rounded-sm" onclick="addDocumentModal.close()">Hủy</button>
                <button type="submit" class="btn text-white border-0 rounded-sm" style="background-color:#0F4C3A" id="documentModalSubmit">{{ $modalIsEdit ? 'Lưu thay đổi' : 'Lưu hồ sơ' }}</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endif

{{-- ── Modal: Xác nhận xóa hồ sơ ────────────────────────────────────────── --}}
@if($canManageDocuments)
<dialog id="deleteModal" class="modal">
    <div class="modal-box max-w-sm rounded-2xl">
        <h3 class="font-bold text-lg text-error">Xác nhận xóa</h3>
        <p class="py-3 text-sm text-base-content/70">
            Bạn có chắc muốn xóa hồ sơ <strong id="deleteItemName" class="text-base-content"></strong>?
        </p>
        <div class="modal-action mt-4">
            <button id="confirmDeleteBtn" class="btn btn-error btn-sm rounded-sm">Xóa</button>
            <button class="btn btn-ghost btn-sm rounded-sm" onclick="deleteModal.close()">Hủy</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endif

{{-- ── Modal: Xem danh sách tệp (read-only) ─────────────────────────────── --}}
@if($canManageDocuments)
<dialog id="viewFilesModal" class="modal">
    <div class="modal-box max-w-md rounded-md p-4 relative" id="viewFilesModalContent" x-data="{ docName: '', media: [] }">
        <button type="button" class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3" onclick="viewFilesModal.close()">✕</button>

        <h3 class="font-bold text-base mb-1" x-text="docName"></h3>
        <p class="text-xs text-gray-400 mb-4" x-text="media.length + ' tệp đính kèm'"></p>

        <div class="space-y-2 max-h-96 overflow-y-auto">
            <template x-for="m in media" :key="m.id">
                <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-200 rounded-lg">
                    <div class="flex items-center gap-2 min-w-0">
                        <svg x-show="m.is_image" class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <svg x-show="!m.is_image" class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="truncate text-sm text-gray-700" x-text="m.name"></span>
                    </div>
                    <a :href="m.url" target="_blank" class="btn btn-ghost btn-xs shrink-0">Mở file</a>
                </div>
            </template>
            <p class="text-sm text-gray-400 text-center py-6" x-show="media.length === 0">Chưa có tệp đính kèm nào.</p>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endif

@endsection

@push('styles')
    @vite(['Modules/Compliance/resources/assets/sass/compliance.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/tom-select.js',
        'Modules/Compliance/resources/assets/js/compliance.js',
    ], 'build/backend')
@endpush
