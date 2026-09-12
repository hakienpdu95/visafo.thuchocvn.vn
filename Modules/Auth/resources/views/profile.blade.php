@extends('layouts.backend')

@section('title', 'Hồ sơ cá nhân')

@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Hồ sơ cá nhân</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Quản lý thông tin tài khoản, mật khẩu và phiên đăng nhập</p>
    </div>
    <a href="{{ route('backend.dashboard') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

<div class="space-y-5">

    {{-- ── Thông tin cá nhân ──────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="card-title text-base mb-5">
                <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Thông tin cá nhân
            </h2>

            <div class="flex items-center gap-3 p-3 bg-base-200/50 rounded-xl mb-5">
                <img src="https://api.dicebear.com/9.x/initials/svg?seed={{ urlencode($user->name ?? 'U') }}&backgroundColor=6366f1&fontFamily=Arial&fontSize=40&fontWeight=700"
                     alt="Avatar" class="w-12 h-12 rounded-full shrink-0">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold truncate text-base-content">{{ $user->name }}</p>
                    <p class="text-xs truncate mt-0.5 text-base-content/60">{{ $user->email }}</p>
                </div>
                <div class="flex gap-1.5 shrink-0">
                    @if($user->trust_level >= 2)
                        <span class="badge badge-info badge-xs">📱 Phone verified</span>
                    @elseif($user->trust_level >= 1)
                        <span class="badge badge-outline badge-xs">✉ Email verified</span>
                    @else
                        <span class="badge badge-ghost badge-xs">Chưa xác minh</span>
                    @endif
                    @if($user->isOrgMember())
                        <span class="badge badge-primary badge-xs">Đang làm việc</span>
                    @else
                        <span class="badge badge-ghost badge-xs">Tự do</span>
                    @endif
                </div>
            </div>

            @if (session('status') === 'profile-information-updated')
                <div class="alert alert-success py-2.5 px-4 mb-5 text-sm">Thông tin đã được cập nhật.</div>
            @endif

            <form method="POST" action="{{ url('user/profile-information') }}">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Họ và tên <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                               class="input input-bordered input-sm w-full @error('name', 'updateProfileInformation') input-error @enderror"
                               placeholder="VD: Nguyễn Văn A" required>
                        @error('name', 'updateProfileInformation')
                        <p class="mt-1 text-xs text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Email <span class="text-error">*</span></span>
                        </label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                               class="input input-bordered input-sm w-full @error('email', 'updateProfileInformation') input-error @enderror"
                               placeholder="ten@congty.com" required>
                        @error('email', 'updateProfileInformation')
                        <p class="mt-1 text-xs text-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex gap-2 pt-4 mt-4 border-t border-base-200">
                    <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Đổi mật khẩu ────────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="card-title text-base mb-5">
                <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Đổi mật khẩu
            </h2>

            @if (session('status') === 'password-updated')
                <div class="alert alert-success py-2.5 px-4 mb-5 text-sm">Mật khẩu đã được cập nhật.</div>
            @endif

            <form method="POST" action="{{ url('user/password') }}">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mật khẩu hiện tại</span>
                        </label>
                        <input type="password" name="current_password"
                               class="input input-bordered input-sm w-full @error('current_password', 'updatePassword') input-error @enderror"
                               placeholder="Nhập mật khẩu hiện tại">
                        @error('current_password', 'updatePassword')
                        <p class="mt-1 text-xs text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mật khẩu mới</span>
                        </label>
                        <input type="password" name="password"
                               class="input input-bordered input-sm w-full @error('password', 'updatePassword') input-error @enderror"
                               placeholder="Tối thiểu 8 ký tự">
                        @error('password', 'updatePassword')
                        <p class="mt-1 text-xs text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Xác nhận mật khẩu mới</span>
                        </label>
                        <input type="password" name="password_confirmation"
                               class="input input-bordered input-sm w-full"
                               placeholder="Nhập lại mật khẩu mới">
                    </div>
                </div>

                <div class="flex gap-2 pt-4 mt-4 border-t border-base-200">
                    <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Đổi mật khẩu
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Tài khoản liên kết ─────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="card-title text-base mb-5">
                <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                Tài khoản liên kết
            </h2>

            @if ($errors->has('social'))
                <div class="alert alert-error py-2.5 px-4 mb-5 text-sm">{{ $errors->first('social') }}</div>
            @endif

            @if (session('social_success'))
                <div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('social_success') }}</div>
            @endif

            <div class="space-y-0">
                @foreach (['google' => 'Google', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn'] as $provider => $label)
                    @php $linked = $user->socialAccounts->firstWhere('provider', $provider) @endphp

                    <div class="flex items-center justify-between py-2.5 border-b border-base-200 last:border-0">
                        <span class="font-medium text-sm">{{ $label }}</span>

                        @if ($linked)
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-base-content/50">{{ $linked->provider_email }}</span>
                                <form method="POST"
                                      action="{{ route('auth.social.unlink', $provider) }}"
                                      onsubmit="return confirm('Bỏ liên kết {{ $label }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-xs text-error">Bỏ liên kết</button>
                                </form>
                            </div>
                        @else
                            <a href="{{ route('auth.social.redirect', $provider) }}" class="btn btn-outline btn-xs">
                                Kết nối
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Session đang hoạt động (SRS01-FR-AUTH-005) ───────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <div class="flex items-center justify-between mb-5">
                <h2 class="card-title text-base">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Session đang hoạt động
                </h2>
                @if($sessions->count() > 1)
                <form method="POST" action="{{ route('auth.sessions.destroy-others') }}" onsubmit="return confirm('Đăng xuất tất cả session khác?')">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-error btn-xs">Đăng xuất session khác</button>
                </form>
                @endif
            </div>

            <div class="space-y-0">
                @foreach($sessions as $s)
                    <div class="flex items-center justify-between text-sm py-2.5 border-b border-base-200 last:border-0">
                        <div class="min-w-0">
                            <p class="truncate">{{ \Illuminate\Support\Str::limit($s->user_agent ?? 'Không rõ thiết bị', 60) }}</p>
                            <p class="text-xs text-base-content/40 mt-0.5">{{ $s->ip_address }} · {{ \Carbon\Carbon::createFromTimestamp($s->last_activity)->diffForHumans() }}</p>
                        </div>
                        @if($s->id === $currentSessionId)
                            <span class="badge badge-success badge-xs shrink-0">Hiện tại</span>
                        @else
                            <form method="POST" action="{{ route('auth.sessions.destroy', $s->id) }}" onsubmit="return confirm('Đăng xuất session này?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-xs shrink-0">Đăng xuất</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

@endsection
