@extends('layouts.backend')
@section('title', $salesOrder->misa_ref_id)

@section('content')
@php $printable = ! $salesOrder->trashed() && auth()->user()->can('print', $salesOrder); @endphp
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content font-mono flex items-center gap-2">
            {{ $salesOrder->misa_ref_id }}
            @if($salesOrder->trashed())
            <span class="badge badge-error font-sans text-xs font-semibold">Đơn đã xóa</span>
            @endif
        </h1>
        @if($salesOrder->trashed())
        <p class="text-sm text-error mt-0.5">Đã xóa lúc {{ $salesOrder->deleted_at->format('H:i d/m/Y') }} — tem của đơn đã bị hủy, chỉ còn để tra cứu lịch sử.</p>
        @endif
        <p class="text-sm text-base-content/50 mt-0.5">{{ $salesOrder->customer_name ?? '—' }}</p>
        <div x-data="{ editing: false }" class="flex items-center gap-2 mt-1 text-sm">
            <span class="text-base-content/50">Ngày giao:</span>
            <span x-show="!editing" class="font-medium">{{ $salesOrder->delivery_date?->format('d/m/Y') ?? '—' }}</span>
            @if($printable)
            <button type="button" x-show="!editing" @click="editing = true" class="link link-primary text-xs">Sửa</button>
            <form x-show="editing" x-cloak method="POST" action="{{ route('backend.sales-orders.delivery-date', $salesOrder) }}" class="flex items-center gap-2">
                @csrf
                @method('PATCH')
                <input type="text" name="delivery_date" required value="{{ $salesOrder->delivery_date?->toDateString() }}" class="input input-bordered input-xs w-32 fp-init">
                <button type="submit" class="btn btn-primary btn-xs">Lưu</button>
                <button type="button" @click="editing = false" class="btn btn-ghost btn-xs">Hủy</button>
            </form>
            @endif
        </div>
        @error('delivery_date')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
        {{-- Giao vận: mốc xuất kho / giao thành công hiện lên timeline trang truy xuất --}}
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-sm">
            <span class="badge badge-sm {{ ['pending' => 'badge-ghost', 'shipping' => 'badge-info', 'delivered' => 'badge-success'][$salesOrder->status] ?? 'badge-ghost' }}">{{ \Modules\SalesOrder\Models\SalesOrder::statusLabels()[$salesOrder->status] ?? $salesOrder->status }}</span>
            @if($salesOrder->delivery_code)
            <span class="text-base-content/50">Vận đơn: <span class="font-mono font-medium text-base-content">{{ $salesOrder->delivery_code }}</span></span>
            @endif
            @if($salesOrder->shipped_at)
            <span class="text-base-content/50">Xuất kho: <span class="font-medium text-base-content">{{ $salesOrder->shipped_at->format('H:i d/m/Y') }}</span></span>
            @endif
            @if($salesOrder->delivered_at)
            <span class="text-base-content/50">Đã giao: <span class="font-medium text-base-content">{{ $salesOrder->delivered_at->format('H:i d/m/Y') }}</span></span>
            @endif
        </div>
        @if($errors->hasAny(['result', 'checked_at', 'note', 'order']))
        <p class="text-xs text-error mt-1">{{ $errors->first() }}</p>
        @endif
    </div>
    <div class="flex items-center gap-2">
        @php
            $lockReason = $salesOrder->lockReason();
            $deleteReason = $salesOrder->deleteBlockReason();
            $canEdit = \App\Support\Permissions\ModuleAccess::update(auth()->user(), 'sales_order', $salesOrder);
            $canDelete = \App\Support\Permissions\ModuleAccess::delete(auth()->user(), 'sales_order', $salesOrder);
        @endphp
        @if($canEdit && ! $salesOrder->trashed())
            @if($lockReason)
            <span class="btn btn-sm btn-outline btn-disabled gap-1.5" title="Không thể sửa: {{ $lockReason }}">Sửa đơn</span>
            @else
            <a href="{{ route('backend.sales-orders.edit', $salesOrder) }}" class="btn btn-sm btn-outline gap-1.5">Sửa đơn</a>
            @endif
        @endif
        @if($canDelete && ! $salesOrder->trashed())
            @if($deleteReason)
            <span class="btn btn-sm btn-outline btn-disabled gap-1.5" title="Không thể xóa: {{ $deleteReason }}">Xóa đơn</span>
            @else
            <form method="POST" action="{{ route('backend.sales-orders.destroy', $salesOrder) }}"
                  onsubmit="return confirm(@js($salesOrder->hasPrintHistory()
                      ? 'Xóa đơn ' . $salesOrder->misa_ref_id . '? Đơn đã in tem: toàn bộ tem của đơn sẽ bị hủy (quét QR báo mã đã bị hủy). Lịch sử in vẫn được lưu.'
                      : 'Xóa đơn ' . $salesOrder->misa_ref_id . '? Đơn và các dòng hàng sẽ bị xóa hẳn, không thể hoàn tác.'))">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline btn-error gap-1.5">Xóa đơn</button>
            </form>
            @endif
        @endif
        @if($printable)
        @if(! $salesOrder->shipped_at)
        <form method="POST" action="{{ route('backend.sales-orders.ship', $salesOrder) }}"
              onsubmit="return confirm('Xác nhận xuất kho đơn này? Hệ thống sẽ ghi thời điểm xuất và sinh mã vận đơn.')">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline gap-1.5">Xuất kho</button>
        </form>
        @elseif(! $salesOrder->delivered_at)
        <form method="POST" action="{{ route('backend.sales-orders.deliver', $salesOrder) }}"
              onsubmit="return confirm('Xác nhận đơn đã giao thành công?')">
            @csrf
            <button type="submit" class="btn btn-sm btn-success gap-1.5">Đã giao</button>
        </form>
        @endif
        <button type="button" class="btn btn-sm gap-1.5 border-0 bg-blue-800 text-white hover:bg-blue-900"
                onclick="window.dispatchEvent(new CustomEvent('open-bulk-print'))">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
            In tem toàn bộ đơn
        </button>
        @endif
        <a href="{{ route('backend.sales-orders.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>
    </div>
</div>

@php
    $canPrint = $printable;
    $itemRows = $salesOrder->items->map(fn ($item) => [
        'id'            => $item->id,
        'line_no'       => $item->line_no,
        'product_name'  => $item->product?->name ?? $item->product_name_raw,
        'name'          => $item->product?->name ?? $item->product_name_raw,
        'name_raw'      => $item->product && \App\Support\ProductNameMismatch::isSuspicious($item->product->name, $item->product_name_raw) ? $item->product_name_raw : null,
        'sku'           => $item->product?->sku,
        'category_name' => $item->product?->category?->name,
        'unit'          => $item->unit_raw,
        'requested_qty' => number_format((float) $item->requested_qty, 3),
        'requested_qty_raw' => (float) $item->requested_qty,
        'actual_qty'    => $item->actual_qty !== null ? number_format((float) $item->actual_qty, 3) : null,
        'attributes_url' => route('backend.sales-orders.items.batch-attributes', $item),
        'label_template_id' => $item->product?->label_template_id,
        'batches_url'   => route('backend.sales-orders.items.batches', $item),
        'partner_products_url' => route('backend.sales-orders.items.partner-products', $item),
        'print_prefill' => $printPrefill[$item->id] ?? null,
        'shelf_life_days' => $item->product?->shelf_life_days,
        'requires_quarantine' => \Modules\GoodsReceipt\Services\BatchDocumentGate::enforced() && \Modules\GoodsReceipt\Services\BatchDocumentGate::requiresQuarantine($item->product),
        'requires_dates' => \Modules\GoodsReceipt\Services\BatchDocumentGate::enforced() && \Modules\GoodsReceipt\Services\BatchDocumentGate::requiresDates($item->product),
        'print_url'     => $canPrint ? route('backend.sales-orders.items.print', $item) : null,
        'qc_url'        => $canPrint ? route('backend.sales-orders.items.quality-checks.store', $item) : null,
        'qc'            => ($last = $item->qualityChecks->last())
            ? ['result' => $last->result->value, 'label' => $last->result->label(), 'at' => $last->checked_at->format('d/m/Y H:i')] : null,
    ])->values();
@endphp

<div class="flex flex-col gap-6">

    @if($printable)
    <div x-data="bulkPrintOrder({{ Js::from(['url' => route('backend.sales-orders.print-all', $salesOrder), 'defaultTemplateId' => $defaultLabelTemplateId, 'vendors' => $vendors]) }})"
         @open-bulk-print.window="openConfirm()" class="contents" x-cloak>

        <div class="alert py-2.5 px-4 text-sm" :class="alertClass" x-show="message" x-transition>
            <span x-text="message"></span>
            <button type="button" class="btn btn-ghost btn-xs ml-auto" @click="message = ''">Đóng</button>
        </div>

        <div class="modal" :class="{ 'modal-open': confirming }" @keydown.escape.window="confirming = false">
            <div class="modal-box max-w-6xl max-h-[90vh] flex flex-col overflow-hidden">
                <h3 class="font-bold text-lg mb-1 shrink-0">In tem toàn bộ đơn</h3>

                <form @submit.prevent="run()" novalidate class="flex flex-col flex-1 min-h-0">
                    <div class="flex-1 min-h-0 overflow-y-auto -mx-1 px-1">
                        <p class="text-sm text-base-content/60 mb-4" x-text="introText"></p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            <div class="form-control sm:col-span-2">
                                <label class="label py-0 pb-1.5" for="bp-label-template">
                                    <span class="label-text font-medium">Mẫu tem in <span class="text-error">*</span></span>
                                    <span class="label-text-alt text-base-content/40 text-xs">Áp dụng cho toàn bộ tem trong lần in này</span>
                                </label>
                                <select id="bp-label-template"
                                        class="select select-bordered select-sm w-full"
                                        :class="{ 'select-error': errors.label_template_id }"
                                        data-ts-placeholder="— Chọn mẫu tem in —">
                                    <option value="">— Chọn mẫu tem in —</option>
                                    @foreach($labelTemplates as $labelTemplate)
                                    <option value="{{ $labelTemplate['value'] }}">{{ $labelTemplate['text'] }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-error" x-show="errors.label_template_id" x-text="errors.label_template_id"></p>
                            </div>

                            <div class="flex flex-col">
                                <label class="block text-sm font-medium text-base-content mb-1" for="bp-mfg">
                                    NSX <span class="text-xs font-normal text-base-content/40 ml-1">Mặc định hôm nay</span>
                                </label>
                                <input id="bp-mfg" type="text" autocomplete="off" placeholder="DD/MM/YYYY"
                                       class="input input-bordered input-sm w-full"
                                       :class="{ 'input-error': errors.mfg_date }">
                                <p class="mt-1 text-xs text-error" x-show="errors.mfg_date" x-text="errors.mfg_date"></p>
                            </div>

                            <div class="flex flex-col">
                                <label class="block text-sm font-medium text-base-content mb-1" for="bp-exp">
                                    HSD <span class="text-error">*</span>
                                    <span class="text-xs font-normal text-base-content/40 ml-1">Mặc định NSX + 2 ngày</span>
                                </label>
                                <input id="bp-exp" type="text" autocomplete="off" placeholder="DD/MM/YYYY"
                                       class="input input-bordered input-sm w-full"
                                       :class="{ 'input-error': errors.exp_date }">
                                <p class="mt-1 text-xs text-error" x-show="errors.exp_date" x-text="errors.exp_date"></p>
                            </div>

                            <div class="form-control">
                                <label class="label py-0 pb-1.5" for="bp-batch-code">
                                    <span class="label-text font-medium">Mã lô</span>
                                    <span class="label-text-alt text-base-content/40 text-xs">Tự sinh theo NSX/HSD</span>
                                </label>
                                <input id="bp-batch-code" type="text" readonly disabled
                                       x-model="form.batchCode" placeholder="LOT-..."
                                       class="input input-bordered input-sm w-full bg-base-200 text-base-content/70 cursor-not-allowed font-mono">
                            </div>

                            <div class="form-control">
                                <label class="label py-0 pb-1.5" for="bp-supplier">
                                    <span class="label-text font-medium">Nguồn cung chung</span>
                                    <span class="label-text-alt text-base-content/40 text-xs">Tuỳ chọn · áp cho mọi mặt hàng chưa sửa riêng</span>
                                </label>
                                <select id="bp-supplier" class="select select-bordered select-sm w-full" data-ts-placeholder="— Không áp dụng —">
                                    <option value="">— Không áp dụng —</option>
                                    @foreach($vendors as $vendor)
                                    <option value="{{ $vendor['value'] }}">{{ $vendor['text'] }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-base-content/50" x-show="globalVendorId" x-text="'Đã áp cho ' + globalAppliedCount + ' mặt hàng.'"></p>
                            </div>
                        </div>

                        <div class="mt-5">
                            <div class="flex flex-wrap items-center justify-between gap-2 pb-1.5">
                                <div class="flex items-center gap-3">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" class="checkbox checkbox-sm checkbox-primary" :checked="allSelected" @change="toggleAll($event.target.checked)">
                                        <span class="label-text font-medium">Danh sách mặt hàng</span>
                                    </label>
                                    <button type="button" class="btn btn-ghost btn-xs text-primary" @click="selectNeeded()" title="Chỉ tick các mặt hàng chưa in hoặc có thay đổi">Chỉ chọn dòng cần in</button>
                                </div>
                                <span class="text-xs text-base-content/50">
                                    Đã chọn <span x-text="selectedItems.length"></span>/<span x-text="items.length"></span> mặt hàng · <span class="font-mono" x-text="totalLabels"></span> tem
                                </span>
                            </div>
                            <p class="text-xs text-base-content/50 pb-2">Mặc định chỉ tick các mặt hàng chưa in hoặc đổi nguồn cung; sửa số tem / khối lượng của mặt hàng nào thì mặt hàng đó tự được tick.</p>

                            <div class="rounded-lg border border-base-200">
                                <template x-for="row in items" :key="row.id">
                                    <div class="border-b border-base-200 last:border-b-0" :class="{ 'bg-error/5': row.blocked }">
                                        <div class="grid grid-cols-[auto_minmax(0,1.3fr)_minmax(0,2fr)_auto] gap-3 items-center px-3 py-2"
                                             :class="{ 'bg-base-200/40': row.editing, 'opacity-60': !row.selected && !row.editing }">
                                            <input type="checkbox" class="checkbox checkbox-sm checkbox-primary" x-model="row.selected" :title="row.selected ? 'Bỏ chọn — không in mặt hàng này' : 'Chọn để in mặt hàng này'">
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium truncate" x-text="row.name" :title="row.name"></p>
                                                <p class="text-xs text-base-content/50 font-mono">
                                                    <span x-text="fmtKg(row.total)"></span> <span x-text="row.unit"></span>
                                                </p>
                                            </div>
                                            <div class="flex flex-wrap gap-1 items-center">
                                                <template x-for="g in row.groups" :key="g.uid">
                                                    <span class="badge badge-sm badge-outline font-mono whitespace-nowrap"
                                                          :class="{ 'badge-error': !groupValid(g) }"
                                                          x-text="fmtKg(g.weight) + ' ' + row.unit + ' × ' + (g.qty || 0) + ' tem'"></span>
                                                </template>
                                                <span class="text-xs text-base-content/50" x-show="!rowMatch(row)"
                                                      x-text="'Tổng tem: ' + fmtKg(rowTotal(row)) + '/' + fmtKg(row.total)"></span>
                                            </div>
                                            <button type="button" class="btn btn-ghost btn-xs btn-square" @click="toggleRow(row)"
                                                    :title="row.editing ? 'Thu gọn' : 'Đổi lô xuất kho / cách chia tem'">
                                                <svg x-show="!row.editing" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.232-6.232a2.5 2.5 0 113.536 3.536L12.536 16.5 8 18l1.5-4.536z"/></svg>
                                                <svg x-show="row.editing" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                            </button>
                                        </div>
                                        <p class="px-3 pb-1 -mt-1 text-xs text-error font-medium" x-show="row.blocked" x-text="'Bị chặn, chưa in: ' + row.blocked"></p>
                                        <div class="flex flex-wrap items-center gap-1 px-3 pb-2 -mt-1">
                                            <span class="badge badge-sm badge-success badge-outline font-mono max-w-full truncate" x-show="row.batchId"
                                                  :title="row.batchText" x-text="'Lô ' + batchCodeOf(row)"></span>
                                            <span class="badge badge-sm bg-base-200 border-base-300 text-base-content/70 max-w-full truncate" x-show="supplierOf(row)"
                                                  :title="supplierOf(row)" x-text="(row.viaGlobal ? 'Theo cấu hình chung - ' : '') + supplierOf(row)"></span>
                                            <span class="badge badge-sm badge-error badge-outline" x-show="!rowHasSource(row)"
                                                  x-text="row.hasBatches ? 'Chưa chọn lô xuất kho / nguồn cung' : 'Chưa chọn nguồn cung'"></span>
                                            <span class="badge badge-sm badge-ghost text-[11px]" x-show="row.origin === 'history' && !sourceChanged(row)">Nguồn theo lần in trước</span>
                                            <span class="badge badge-sm badge-ghost text-[11px]" x-show="row.origin === 'customer' && !row.dirty && !row.viaGlobal"
                                                  title="Nhà cung cấp / hàng hóa NCC của lần in gần nhất cùng mặt hàng, cùng khách hàng — có thể đổi">Nguồn theo đơn trước của khách</span>
                                            <span class="badge badge-sm badge-ghost font-mono text-[11px]" x-show="row.printed > 0" x-text="'Đã in: ' + printedSummary(row)"></span>
                                            <span class="badge badge-sm badge-info text-[11px]" x-show="row.printed > 0 && sourceChanged(row)">Cập nhật NCC cho tem trùng khối lượng</span>
                                            <span class="badge badge-sm badge-primary text-[11px]" x-show="row.printed > 0 && appendCount(row) > 0"
                                                  x-text="'+' + appendCount(row) + ' mã mới' + (reuseCount(row) > 0 ? ' · dùng lại ' + reuseCount(row) + ' mã cũ' : '')"></span>
                                            <span class="badge badge-sm badge-info badge-outline text-[11px]" x-show="row.printed > 0 && appendCount(row) === 0"
                                                  x-text="'Trùng tem đã in · in lại ' + reuseCount(row) + ' mã cũ'"></span>
                                        </div>

                                        <div x-show="row.editing" class="px-3 pb-3 bg-base-200/40">
                                            <p class="text-xs text-info mb-2" x-show="row.printed > 0">
                                                Đã in <span class="font-mono" x-text="printedSummary(row)"></span>. Dòng nào trùng khối lượng/tem với tem đã in thì dùng lại mã cũ (đã dán lên hàng);
                                                phần không trùng sẽ tạo mã TXNG mới để in. Tem cũ không bao giờ tự bị hủy.
                                            </p>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                                                @include('salesorder::partials.print-source-fields')
                                            </div>
                                            <div class="rounded-lg border border-base-200 bg-base-100 overflow-hidden">
                                                <div class="grid grid-cols-[1fr_auto_1fr_auto] gap-2 items-center px-3 py-1.5 bg-base-200/50 text-xs font-medium text-base-content/60">
                                                    <span x-text="'Số lượng/Tem (' + row.unit + ')'"></span><span></span><span>Số lượng Tem</span><span class="w-14"></span>
                                                </div>
                                                <template x-for="(group, index) in row.groups" :key="group.uid">
                                                    <div class="grid grid-cols-[1fr_auto_1fr_auto] gap-2 items-center px-3 py-1.5 border-t border-base-200">
                                                        <input type="number" step="0.001" min="0.001" inputmode="decimal" x-model="group.weight"
                                                               class="input input-bordered input-sm w-full font-mono"
                                                               :class="{ 'input-error': !(Number(group.weight) > 0) }">
                                                        <span class="text-base-content/40">×</span>
                                                        <input type="number" step="1" min="1" max="200" inputmode="numeric" x-model="group.qty"
                                                               class="input input-bordered input-sm w-full font-mono"
                                                               :class="{ 'input-error': !(Number.isInteger(Number(group.qty)) && Number(group.qty) >= 1) }">
                                                        <button type="button" class="btn btn-ghost btn-sm text-error w-14" @click="removeGroup(row, index)">Xóa</button>
                                                        <span class="col-span-4 -mt-1 text-[11px]" x-show="groupNote(row, group)"
                                                              :class="groupNote(row, group) === 'Mã cũ' ? 'text-base-content/50' : 'text-primary'"
                                                              x-text="groupNote(row, group)"></span>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="flex flex-wrap items-center justify-between gap-2 mt-2">
                                                <div class="flex gap-1">
                                                    <button type="button" class="btn btn-ghost btn-xs gap-1 text-primary" @click="addGroup(row)">
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                        Thêm dòng cấu hình
                                                    </button>
                                                    <button type="button" class="btn btn-ghost btn-xs text-base-content/60" @click="autoSplit(row)">Đặt lại</button>
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <p class="text-xs" :class="rowMatch(row) ? 'text-success' : 'text-base-content/60'">
                                                        Tổng số lượng tem: <span class="font-mono" x-text="fmtKg(rowTotal(row))"></span> <span x-text="row.unit"></span>
                                                        / Tổng yêu cầu: <span class="font-mono" x-text="fmtKg(row.total)"></span> <span x-text="row.unit"></span>
                                                    </p>
                                                    <button type="button" class="btn btn-primary btn-xs" @click="row.editing = false">Xong</button>
                                                </div>
                                            </div>
                                            <p class="mt-2 text-xs text-primary" x-show="row.printed > 0"
                                               x-text="appendCount(row) > 0
                                                   ? 'Khi bấm In: tạo ' + appendCount(row) + ' mã TXNG mới (' + appendDetail(row) + ')' + (reuseCount(row) > 0 ? ', dùng lại ' + reuseCount(row) + ' mã cũ trùng khối lượng' : '') + '.'
                                                   : 'Khi bấm In: toàn bộ trùng tem đã in — in lại ' + reuseCount(row) + ' mã cũ, không tạo mã mới.'"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <p class="mt-1 text-xs text-error" x-show="errors.items" x-text="errors.items"></p>
                            <p class="mt-2 alert alert-warning py-2 px-3 text-xs" x-show="notice" x-text="notice"></p>
                        </div>

                    </div>

                        <div class="modal-action mt-4 items-center shrink-0">
                            <p class="text-xs text-base-content/50 mr-auto" x-show="!form.template">Vui lòng chọn mẫu tem in</p>
                            <p class="flex items-start gap-1.5 text-xs text-warning mr-auto" x-show="form.template && missingSourceCount > 0">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                <span x-text="'Lưu ý: Có ' + missingSourceCount + ' mặt hàng chưa chọn Nguồn cung (Tem in ra sẽ không có thông tin nguồn gốc)'"></span>
                            </p>
                            <button type="submit" class="btn btn-sm border-0 bg-blue-800 text-white hover:bg-blue-900"
                                    :disabled="!canRun">
                                <span class="loading loading-spinner loading-xs" x-show="submitting"></span>
                                <span x-text="sendItems.length > 0 ? 'In ' + sendItems.length + ' mặt hàng (' + totalLabels + ' tem)' : 'Chọn mặt hàng để in'"></span>
                            </button>
                            <button type="button" class="btn btn-ghost btn-sm" @click="confirming = false">Hủy</button>
                        </div>
                </form>
            </div>
            <div class="modal-backdrop" @click="confirming = false"></div>
        </div>
    </div>
    @endif

    <div class="card w-full bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-5">
            <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-4">Chi tiết đơn</p>

            <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-5 text-sm">
                <div class="space-y-4">
                    <div>
                        <dt class="text-xs text-base-content/50 mb-0.5">Khách hàng</dt>
                        <dd class="font-medium">{{ $salesOrder->customer_name ?? '—' }}</dd>
                        @if($salesOrder->customer)
                        <dd class="text-xs text-base-content/50 font-mono mt-0.5">{{ $salesOrder->customer->customer_code }}</dd>
                        @endif
                    </div>
                    <div>
                        <dt class="text-xs text-base-content/50 mb-0.5">Địa chỉ giao hàng</dt>
                        <dd>{{ $salesOrder->delivery_address ?? '—' }}</dd>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <dt class="text-xs text-base-content/50 mb-1">Trạng thái</dt>
                        <dd><span class="badge badge-warning badge-soft badge-sm">{{ \Modules\SalesOrder\Models\SalesOrder::statusLabels()[$salesOrder->status] ?? $salesOrder->status }}</span></dd>
                    </div>
                    <div>
                        <dt class="text-xs text-base-content/50 mb-0.5">File nguồn</dt>
                        <dd class="font-mono text-xs break-all">{{ $salesOrder->source_file_name ?? 'Nhập tay' }}</dd>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <dt class="text-xs text-base-content/50 mb-0.5">{{ $salesOrder->isManual() ? 'Người tạo' : 'Người import' }}</dt>
                        <dd>{{ $salesOrder->importedBy?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-base-content/50 mb-0.5">Nhập lúc</dt>
                        <dd>{{ $salesOrder->created_at?->format('d/m/Y H:i') }}</dd>
                    </div>
                </div>
            </dl>
        </div>
    </div>

    <div class="card w-full bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-x-auto tabulator-daisy">
            <div id="sales-order-items-table" data-rows="{{ json_encode($itemRows, JSON_UNESCAPED_UNICODE) }}"></div>
        </div>
    </div>

</div>

@if($printable)
<div x-data="printLabelModal()" @open-print-label.window="openFor($event.detail)" @sales-order-prefill.window="applyPrefill($event.detail)" x-cloak>
    <div class="modal" :class="{ 'modal-open': open }" @keydown.escape.window="close()">
        <div class="modal-box max-w-6xl max-h-[90vh] flex flex-col overflow-hidden">

            <h3 class="card-title text-base mb-5 shrink-0">
                <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/>
                </svg>
                Cấu hình In Tem
            </h3>

            <div class="divider my-4 text-xs text-base-content/30 shrink-0" x-text="item?.product_name"></div>

            <form @submit.prevent="submit()" novalidate class="flex flex-col flex-1 min-h-0">
                <div class="flex-1 min-h-0 overflow-y-auto -mx-1 px-1">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5" for="ts-label-template">
                            <span class="label-text font-medium">Mẫu tem in</span>
                            <span class="label-text-alt text-base-content/40 text-xs">Để trống sẽ dùng mẫu mặc định</span>
                        </label>
                        <select id="ts-label-template" name="label_template_id"
                                class="select select-bordered select-sm w-full"
                                data-ts-placeholder="— Mặc định: Mẫu tem truy xuất VISAFO - Cỡ lớn (Khổ giấy in nhãn 100x75mm) —"
                                data-default-template-id="{{ $defaultLabelTemplateId }}">
                            <option value="">— Mặc định: Mẫu tem truy xuất VISAFO - Cỡ lớn (Khổ giấy in nhãn 100x75mm) —</option>
                            @foreach($labelTemplates as $labelTemplate)
                            <option value="{{ $labelTemplate['value'] }}">{{ $labelTemplate['text'] }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-error" x-show="errors.label_template_id" x-text="errors.label_template_id"></p>
                    </div>

                    <div class="form-control sm:col-span-2">
                        <div class="flex flex-wrap items-center justify-between gap-2 pb-1.5">
                            <span class="label-text font-medium">Nhóm tem <span class="text-error">*</span></span>
                            <span class="badge badge-sm badge-ghost font-mono text-[11px]" x-show="printedGroups.length > 0" x-text="'Đã in: ' + printedSummary"></span>
                        </div>

                        <div class="rounded-lg border border-base-200 overflow-hidden">
                            <div class="grid grid-cols-[1fr_auto_1fr_auto] gap-2 items-center px-3 py-2 bg-base-200/50 text-xs font-medium text-base-content/60">
                                <span x-text="'Số lượng/Tem (' + (item?.unit || 'kg') + ')'"></span><span></span><span>Số lượng Tem</span><span class="w-14"></span>
                            </div>
                            <template x-for="(group, index) in form.groups" :key="group.uid">
                                <div class="grid grid-cols-[1fr_auto_1fr_auto] gap-2 items-center px-3 py-2 border-t border-base-200">
                                    <input type="number" step="0.001" min="0.001" inputmode="decimal" x-model="group.weight"
                                           class="input input-bordered input-sm w-full font-mono"
                                           :class="{ 'input-error': !(Number(group.weight) > 0) }">
                                    <span class="text-base-content/40">×</span>
                                    <input type="number" step="1" min="1" max="200" inputmode="numeric" x-model="group.qty"
                                           class="input input-bordered input-sm w-full font-mono"
                                           :class="{ 'input-error': !(Number.isInteger(Number(group.qty)) && Number(group.qty) >= 1) }">
                                    <button type="button" class="btn btn-ghost btn-sm text-error w-14" @click="removeGroup(index)" title="Xóa dòng này">Xóa</button>
                                    <span class="col-span-4 -mt-1 text-[11px]" x-show="groupNote(group)"
                                          :class="groupNote(group) === 'Mã cũ' ? 'text-base-content/50' : 'text-primary'"
                                          x-text="groupNote(group)"></span>
                                </div>
                            </template>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2 mt-2">
                            <div class="flex gap-1">
                                <button type="button" class="btn btn-ghost btn-sm gap-1.5 text-primary" @click="addGroup()">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Thêm dòng cấu hình
                                </button>
                                <button type="button" class="btn btn-ghost btn-sm text-base-content/60" @click="autoSplit()">Đặt lại</button>
                            </div>
                            <p class="text-sm" :class="groupsMatch ? 'text-success' : 'text-base-content/60'">
                                Tổng số lượng tem: <span class="font-mono" x-text="fmtKg(groupsTotal)"></span> <span x-text="item?.unit || 'kg'"></span>
                                / Tổng yêu cầu: <span class="font-mono" x-text="fmtKg(requiredTotal)"></span> <span x-text="item?.unit || 'kg'"></span>
                                <span class="text-base-content/50 font-normal" x-text="'(' + labelCount + ' tem)'"></span>
                            </p>
                        </div>
                        <p class="mt-2 text-xs text-primary" x-show="printedGroups.length > 0"
                           x-text="appendCount > 0
                               ? 'Khi bấm In: tạo ' + appendCount + ' mã TXNG mới' + (reuseCount > 0 ? ', dùng lại ' + reuseCount + ' mã cũ trùng khối lượng' : '') + '.'
                               : 'Khi bấm In: toàn bộ trùng tem đã in — in lại ' + reuseCount + ' mã cũ, không tạo mã mới.'"></p>

                        <p class="mt-1 text-xs text-error" x-show="labelCount > 200">Mỗi lần chỉ in tối đa 200 tem.</p>
                        <p class="mt-1 text-xs text-error" x-show="errors.label_groups" x-text="errors.label_groups"></p>
                    </div>

                    {{-- NSX/HSD xếp chồng label-trên-input trong từng ô, 2 ô cạnh nhau trên màn hình vừa/lớn. --}}
                    <div class="sm:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div class="flex flex-col">
                            <label class="block text-sm font-medium text-base-content mb-1" for="fp-label-mfg">
                                NSX <span class="text-error" x-show="item?.requires_dates">*</span>
                                <span class="text-xs font-normal text-base-content/40 ml-1" x-text="item?.requires_dates ? 'Theo bao bì / lô nhập kho' : 'Mặc định hôm nay'"></span>
                            </label>
                            <input id="fp-label-mfg" type="text" name="mfg_date" autocomplete="off" placeholder="DD/MM/YYYY"
                                   class="input input-bordered input-sm w-full"
                                   :class="{ 'input-error': errors.mfg_date }">
                            <p class="mt-1 text-xs text-error" x-show="errors.mfg_date" x-text="errors.mfg_date"></p>
                        </div>

                        <div class="flex flex-col">
                            <label class="block text-sm font-medium text-base-content mb-1" for="fp-label-exp">
                                HSD <span class="text-error">*</span>
                                <span class="text-xs font-normal text-base-content/40 ml-1" x-show="item?.shelf_life_days"
                                      x-text="'NSX + ' + item?.shelf_life_days + ' ngày'"></span>
                            </label>
                            <input id="fp-label-exp" type="text" name="exp_date" autocomplete="off" placeholder="DD/MM/YYYY"
                                   class="input input-bordered input-sm w-full"
                                   :class="{ 'input-error': errors.exp_date }">
                            <p class="mt-1 text-xs text-base-content/40" x-show="item?.shelf_life_days && !item?.requires_dates && !errors.exp_date">
                                Tự tính theo số ngày bảo quản của sản phẩm, vẫn có thể chỉnh tay.
                            </p>
                            <p class="mt-1 text-xs text-error" x-show="errors.exp_date" x-text="errors.exp_date"></p>
                        </div>

                        <p class="md:col-span-2 text-xs text-warning" x-show="item?.requires_dates">
                            Gia vị / Đồ khô: bắt buộc ghi đúng NSX và HSD in trên bao bì (Thông báo 26/TB-VAS) — chọn lô nhập kho để điền tự động.
                        </p>

                    </div>

                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5" for="pl-batch-code">
                            <span class="label-text font-medium">Mã lô</span>
                            <span class="label-text-alt text-base-content/40 text-xs">Tự sinh theo NSX/HSD</span>
                        </label>
                        <input id="pl-batch-code" type="text" name="batch_code" readonly disabled
                               x-model="form.batchCode" placeholder="LOT-..."
                               class="input input-bordered input-sm w-full bg-base-200 text-base-content/70 cursor-not-allowed font-mono">
                    </div>

                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5" for="ts-batch">
                            <span class="label-text font-medium">Lô nhập kho</span>
                            <span class="label-text-alt text-base-content/40 text-xs">Chọn lô để truy vết chính xác khi thu hồi</span>
                        </label>
                        <select id="ts-batch" class="select select-bordered select-sm w-full" data-ts-placeholder="— Không chọn lô —">
                            <option value="">— Không chọn lô —</option>
                        </select>
                        <p class="mt-1 text-xs text-base-content/40" x-show="!loadingBatches && batchCount === 0">Chưa có lô nhập kho nào cho mặt hàng này.</p>
                        <p class="mt-1 text-xs text-error" x-show="errors.product_batch_id" x-text="errors.product_batch_id"></p>
                    </div>

                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5" for="ts-supplier">
                            <span class="label-text font-medium">Nguồn cung</span>
                            <span class="label-text-alt text-xs" :class="sourceOrigin ? 'text-info' : 'text-base-content/40'"
                                  x-text="sourceOrigin === 'customer' ? 'Theo đơn trước của khách — có thể đổi' : (sourceOrigin === 'history' ? 'Theo lần in trước — có thể đổi' : 'Tuỳ chọn')"></span>
                        </label>

                        <div x-show="!form.supplierManual">
                            <select id="ts-supplier" name="vendor_id"
                                    class="select select-bordered select-sm w-full"
                                    data-ts-placeholder="— Chọn nhà cung cấp —">
                                <option value="">— Chọn nhà cung cấp —</option>
                                @foreach($vendors as $vendor)
                                <option value="{{ $vendor['value'] }}" data-text="{{ $vendor['text'] }}">{{ $vendor['text'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="form.supplierManual">
                            <input id="pl-supplier-manual" type="text" name="supplier_name" maxlength="255"
                                   x-model="form.supplierText" placeholder="VD: HTX Rau sạch Đà Lạt"
                                   class="input input-bordered input-sm w-full"
                                   :class="{ 'input-error': errors.supplier_name }">
                        </div>

                        <label class="label cursor-pointer justify-start gap-2 py-1.5" for="pl-supplier-manual-toggle">
                            <input id="pl-supplier-manual-toggle" type="checkbox" x-model="form.supplierManual"
                                   @change="onSupplierManualToggle()" class="checkbox checkbox-xs">
                            <span class="label-text text-xs">Khác / Nhập tay</span>
                        </label>
                        <p class="text-xs text-warning" x-show="form.supplierManual">Nguồn cung nhập tay sẽ không truy vết được theo nhà cung cấp.</p>

                        <p class="mt-1 text-xs text-error" x-show="errors.supplier_name" x-text="errors.supplier_name"></p>
                    </div>

                    <div class="form-control sm:col-span-2" x-show="!form.supplierManual">
                        <label class="label py-0 pb-1.5" for="ts-partner">
                            <span class="label-text font-medium">Hàng hóa nhà cung cấp</span>
                            <span class="label-text-alt text-base-content/40 text-xs" x-show="loadingPartners">Đang tải…</span>
                        </label>
                        <select id="ts-partner" class="select select-bordered select-sm w-full" data-ts-placeholder="— Chọn nguồn cung trước —">
                            <option value="">— Chọn nguồn cung trước —</option>
                        </select>
                        <p class="mt-1 text-xs text-error" x-show="errors.partner_product_id" x-text="errors.partner_product_id"></p>
                    </div>

                </div>

                <div class="divider my-4 text-xs text-base-content/30">Thông tin in bổ sung</div>

                <div class="space-y-2">
                    <p class="text-xs text-base-content/40" x-show="loadingAttributes">Đang tải thông tin của lô hàng...</p>
                    <p class="text-xs text-base-content/40" x-show="!loadingAttributes && attributes.length === 0">
                        Chưa có thông tin bổ sung. Bấm "Thêm thông tin" để in thêm HDSD, Liều dùng, Bảo quản...
                    </p>

                    <template x-for="(attr, index) in attributes" :key="attr.uid">
                        <div class="grid grid-cols-[1fr_1.6fr_auto] gap-2 items-center">
                            <input type="text" maxlength="100" x-model="attr.key" placeholder="Tên thông tin (VD: HDSD)"
                                   class="input input-bordered input-sm w-full">
                            <input type="text" maxlength="1000" x-model="attr.value" placeholder="Nội dung"
                                   class="input input-bordered input-sm w-full">
                            <button type="button" class="btn btn-ghost btn-sm text-error" @click="removeAttribute(index)" title="Xóa dòng này">Xóa</button>
                        </div>
                    </template>

                    <p class="text-xs text-error" x-show="errors.extra_attributes" x-text="errors.extra_attributes"></p>

                    <button type="button" class="btn btn-ghost btn-sm gap-1.5 text-primary" @click="addAttribute()">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Thêm thông tin
                    </button>
                </div>

                <div class="alert alert-error py-2 px-3 mt-4 text-xs font-medium" x-show="quarantineBlocked && !loadingBatches" x-text="quarantineMessage"></div>
                <div class="alert alert-warning py-2 px-3 mt-4 text-xs" x-show="message" x-text="message"></div>
                <div class="alert alert-warning py-2 px-3 mt-4 text-xs" x-show="blockedUrl">
                    Trình duyệt đã chặn cửa sổ in. <a :href="blockedUrl" target="_blank" class="link font-semibold">Bấm vào đây để mở tem</a>.
                </div>
                </div>

                <div class="flex items-center gap-3 pt-4 mt-4 border-t border-base-200 shrink-0">
                    <p class="text-xs text-base-content/40" x-show="!canSubmit && !submitting">
                        <span x-show="quarantineBlocked" class="text-error">Thiếu Phiếu kiểm dịch Thú y — không thể in tem</span>
                        <span x-show="!quarantineBlocked && datesMissing" class="text-error">Gia vị / Đồ khô: nhập đủ NSX và HSD để in tem</span>
                        <span x-show="!quarantineBlocked && !datesMissing">Nhập khối lượng &gt; 0, số lượng tem hợp lệ và HSD để in tem</span>
                    </p>
                    <div class="ml-auto flex gap-2">
                        <button type="button" class="btn btn-ghost btn-sm" @click="close()">Hủy</button>
                        <button type="submit" class="btn btn-primary btn-sm gap-1.5" :disabled="!canSubmit">
                            <span class="loading loading-spinner loading-xs" x-show="submitting"></span>
                            <span x-text="submitting ? 'Đang xử lý...' : 'In tem'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-backdrop" @click="close()"></div>
    </div>
</div>
@endif
@if($printable)
<dialog id="itemQcModal" class="modal">
    <div class="modal-box max-w-md">
        <h3 class="font-bold text-lg">QC trước xuất</h3>
        <p class="text-sm text-base-content/60 mt-1" id="itemQcModalName"></p>
        <form id="itemQcForm" method="POST" class="mt-4 space-y-4">
            @csrf
            <div class="form-control">
                <label class="label py-0.5" for="iqc-checked-at"><span class="label-text text-xs font-medium">Thời điểm kiểm tra</span></label>
                <input id="iqc-checked-at" name="checked_at" type="datetime-local" class="input input-sm input-bordered w-full">
            </div>
            <div class="form-control">
                <span class="label-text text-xs font-medium mb-1">Kết quả</span>
                <div class="flex gap-4">
                    @foreach(\Modules\GoodsReceipt\Enums\QualityCheckResult::cases() as $result)
                    <label class="label cursor-pointer gap-2 py-0">
                        <input type="radio" name="result" value="{{ $result->value }}" class="radio radio-sm {{ $result === \Modules\GoodsReceipt\Enums\QualityCheckResult::Pass ? 'radio-success' : 'radio-error' }}" @checked($loop->first) required>
                        <span class="label-text text-sm">{{ $result->label() }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            <div class="form-control">
                <label class="label py-0.5" for="iqc-note"><span class="label-text text-xs font-medium">Ghi chú (nội bộ, không công khai)</span></label>
                <textarea id="iqc-note" name="note" maxlength="500" rows="2" class="textarea textarea-bordered textarea-sm w-full"></textarea>
            </div>
            <div class="modal-action mt-6">
                <button type="submit" class="btn btn-primary btn-sm">Lưu</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="itemQcModal.close()">Hủy</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endif
@endsection

@push('styles')
    <x-tabulator-theme />
    @vite(['Modules/SalesOrder/resources/assets/sass/salesorder.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/toastify.js',
        'Modules/SalesOrder/resources/assets/js/salesorder.js',
    ], 'build/backend')
@endpush
