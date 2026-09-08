@extends('layouts.backend')
@section('title', 'Lịch sử version — ' . $organization->name)

@section('content')
<div class="max-w-3xl">
    <a href="{{ route('backend.organizations.show', $organization) }}" class="link link-hover text-sm text-base-content/50 mb-3 inline-block">&larr; {{ $organization->name }}</a>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Lịch sử version cấu hình</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Mỗi lần publish tạo 1 deployment snapshot bất biến — SRS01-FR-ORG-005/008.</p>
        </div>
    </div>

    @can('update', $organization)
    <div class="bg-base-100 border border-base-200 rounded-xl p-4 mb-5">
        <h2 class="text-sm font-semibold text-base-content/60 mb-3">Publish version mới</h2>
        <form method="POST" action="{{ route('backend.organizations.publish', $organization) }}" class="flex flex-wrap items-end gap-2">
            @csrf
            <div class="form-control flex-1 min-w-[240px]">
                <label class="label label-text text-xs py-0.5">Lý do thay đổi (tùy chọn)</label>
                <input type="text" name="change_reason" class="input input-sm input-bordered w-full" placeholder="VD: cập nhật timezone, thêm retention policy">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Publish</button>
        </form>
    </div>
    @endcan

    <div class="overflow-x-auto bg-base-100 border border-base-200 rounded-xl shadow-sm">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Version</th>
                    <th>Trạng thái</th>
                    <th>Checksum</th>
                    <th>Lý do</th>
                    <th>Approved by</th>
                    <th>Effective</th>
                </tr>
            </thead>
            <tbody>
                @forelse($versions as $v)
                    <tr>
                        <td class="font-mono">v{{ $v->version }}</td>
                        <td><span class="badge {{ $v->status->badgeClass() }} badge-sm">{{ $v->status->label() }}</span></td>
                        <td class="font-mono text-xs">{{ substr($v->checksum, 0, 12) }}…</td>
                        <td class="text-sm">{{ $v->change_reason ?: '—' }}</td>
                        <td class="text-xs text-base-content/60">{{ $v->approvedByUser?->name ?? '—' }}</td>
                        <td class="text-xs text-base-content/60">{{ $v->effective_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-base-content/40 py-6">Chưa có version nào — bấm "Publish" để tạo bản đầu tiên.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
