@extends('layouts.backend')
@section('title', 'Báo cáo Truy vết liên thông')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Báo cáo Truy vết liên thông</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Truy vết Farm-to-Fork: Khách hàng → Sản phẩm Visafo → Nhà cung cấp → Hồ sơ pháp lý</p>
    </div>
</div>

<div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
    <div class="card-body py-4 px-5">
        <form method="GET" action="{{ route('backend.traceability.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="form-control flex-1 min-w-64 max-w-md">
                <label class="label py-0 pb-1.5">
                    <span class="label-text text-xs font-medium">Chọn khách hàng</span>
                </label>
                <select name="customer_id" onchange="this.form.submit()"
                        class="select select-bordered select-sm w-full">
                    <option value="">— Chọn khách hàng để xem báo cáo truy vết —</option>
                    @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" @selected(request('customer_id') === $customer->id)>
                        {{ $customer->name }}@if($customer->customer_code) ({{ $customer->customer_code }}) @endif
                    </option>
                    @endforeach
                </select>
            </div>
            <noscript><button type="submit" class="btn btn-primary btn-sm">Xem báo cáo</button></noscript>
        </form>
    </div>
</div>

@if($selectedCustomer)

    <div class="flex items-center gap-2 mb-4">
        <h2 class="text-lg font-semibold text-base-content">{{ $selectedCustomer->name }}</h2>
        <span class="badge badge-ghost badge-sm">{{ $selectedCustomer->products->count() }} mặt hàng</span>
    </div>

    @if($rows->isEmpty())
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body py-10 text-center text-sm text-base-content/40">
            Khách hàng này chưa được gán mặt hàng Visafo nào.
        </div>
    </div>
    @else
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Sản phẩm cung cấp (Visafo)</th>
                        <th>Nguồn cung (Nhà cung cấp)</th>
                        <th>Tình trạng Hồ sơ NCC</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                    <tr>
                        @if($row['isFirst'])
                        <td rowspan="{{ $row['rowspan'] }}" class="align-top">
                            <p class="font-semibold text-sm">{{ $row['product']->name }}</p>
                            <p class="text-xs text-base-content/40 font-mono">{{ $row['product']->sku }}</p>
                        </td>
                        @endif

                        <td class="align-top">
                            @if($row['partnerProduct'])
                            <p class="text-sm font-medium">{{ $row['partnerProduct']->vendor?->name ?? '—' }}</p>
                            @if($row['partnerProduct']->manufacturer_name)
                            <p class="text-xs text-base-content/50">NSX/nguồn gốc: {{ $row['partnerProduct']->manufacturer_name }}</p>
                            @endif
                            @if($row['partnerProduct']->origin_address)
                            <p class="text-xs text-base-content/40">{{ $row['partnerProduct']->origin_address }}</p>
                            @endif
                            @else
                            <span class="text-xs text-base-content/40">Chưa liên kết hàng hóa NCC</span>
                            @endif
                        </td>

                        <td class="align-top">
                            <span class="badge badge-sm badge-soft
                                @class([
                                    'badge-success' => $row['status']['level'] === 'green',
                                    'badge-warning' => $row['status']['level'] === 'yellow',
                                    'badge-error'   => $row['status']['level'] === 'red',
                                ])">
                                {{ $row['status']['label'] }}
                            </span>
                            @if(!empty($row['status']['docs']) && $row['status']['docs']->isNotEmpty())
                            <ul class="mt-1.5 space-y-0.5">
                                @foreach($row['status']['docs'] as $doc)
                                <li class="text-xs text-base-content/60">
                                    {{ $doc->documentType?->name ?? '—' }}
                                    @if($doc->expiration_date)
                                    — Hạn {{ $doc->expiration_date->format('d/m/Y') }}
                                    @if($doc->isExpired())
                                    <span class="text-error">(đã hết hạn)</span>
                                    @elseif($doc->isExpiringWithinDays(30))
                                    <span class="text-warning">(sắp hết hạn)</span>
                                    @endif
                                    @endif
                                </li>
                                @endforeach
                            </ul>
                            @endif
                        </td>

                        <td class="align-top text-right">
                            @if($row['partnerProduct']?->vendor)
                            <a href="{{ route('backend.vendors.show', $row['partnerProduct']->vendor) }}" class="btn btn-ghost btn-xs">Xem hồ sơ NCC</a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

@endif

@endsection
