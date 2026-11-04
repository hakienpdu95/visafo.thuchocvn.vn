<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Turnstile
    |--------------------------------------------------------------------------
    | Keys-as-switch: set cả 2 keys → Turnstile tự động bật trên non-local.
    | Bỏ trống → skip (+ warning log trên production).
    |
    | Test keys luôn pass (staging / CI):
    |   TURNSTILE_SITE_KEY=1x00000000000000000000AA
    |   TURNSTILE_SECRET_KEY=1x0000000000000000000000000000000AA
    */
    'turnstile' => [
        'key'    => env('TURNSTILE_SITE_KEY', ''),
        'secret' => env('TURNSTILE_SECRET_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Social OAuth Providers
    |--------------------------------------------------------------------------
    | Credentials lấy từ:
    |   Google:   https://console.cloud.google.com → Credentials → OAuth 2.0
    |   Facebook: https://developers.facebook.com → My Apps → Settings
    |   LinkedIn: https://www.linkedin.com/developers/apps → Auth tab
    |
    | Callback URL pattern: {APP_URL}/auth/social/{provider}/callback
    |
    | social_providers: map route-param → Socialite driver name.
    | Đây là nguồn duy nhất cho allowlist + driver lookup — thêm provider
    | mới chỉ cần thêm 1 entry ở đây, không cần sửa code.
    */
    'social_providers' => [
        'google'   => 'google',
        'facebook' => 'facebook',
        'linkedin' => 'linkedin-openid',
    ],

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI', '/auth/social/google/callback'),
    ],

    'facebook' => [
        'client_id'     => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect'      => env('FACEBOOK_REDIRECT_URI', '/auth/social/facebook/callback'),
    ],

    'linkedin-openid' => [
        'client_id'     => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect'      => env('LINKEDIN_REDIRECT_URI', '/auth/social/linkedin/callback'),
    ],

    'hanoicheck' => [
        'enabled'          => (bool) env('HANOICHECK_ENABLED', false),
        'fake'             => (bool) env('HANOICHECK_FAKE', true),
        'environment'      => env('HANOICHECK_ENV', 'sandbox'),
        'base_url'         => rtrim((string) env('HANOICHECK_BASE_URL', ''), '/'),
        'client_id'        => env('HANOICHECK_CLIENT_ID'),
        'client_secret'    => env('HANOICHECK_CLIENT_SECRET'),
        'hmac_secret'      => env('HANOICHECK_HMAC_SECRET'),
        'timeout'          => (int) env('HANOICHECK_TIMEOUT', 30),
        'token_expiry_buffer' => (int) env('HANOICHECK_TOKEN_EXPIRY_BUFFER', 300),
        'refresh_token_ttl'   => 30 * 24 * 3600,
        'queue'            => env('HANOICHECK_QUEUE', 'hanoicheck'),
        'debounce_seconds' => (int) env('HANOICHECK_DEBOUNCE_SECONDS', 15),
        'settle_seconds'   => (int) env('HANOICHECK_SETTLE_SECONDS', 5),
        'allocation_tolerance' => (float) env('HANOICHECK_ALLOCATION_TOLERANCE', 0),
        'order_refresh_days'   => (int) env('HANOICHECK_ORDER_REFRESH_DAYS', 14),
        'backoff'          => [60, 300, 1800, 7200],
        'defaults' => [
            'ma_co_so'        => env('HANOICHECK_FACILITY_CODE', 'CS_VISAFO'),
            'ma_kho'          => env('HANOICHECK_WAREHOUSE_CODE', 'KHO_VISAFO'),
            'ma_khau'         => env('HANOICHECK_STEP_CODE', 'NHAP_KHO'),
            'ten_khau'        => env('HANOICHECK_STEP_NAME', 'Nhập kho'),
            'ma_quy_trinh'    => env('HANOICHECK_PROCESS_CODE', 'QT_CUNG_UNG_THUC_PHAM'),
            'ten_quy_trinh'   => env('HANOICHECK_PROCESS_NAME', 'Quy trình cung ứng thực phẩm'),
            'ma_loai_sp'      => env('HANOICHECK_DEFAULT_FOOD_CATEGORY_ID'),
            'nhom_thuc_pham'  => array_values(array_filter(array_map('intval', explode(',', (string) env('HANOICHECK_DEFAULT_FOOD_GROUP_IDS', ''))))),
            'warehouse_area'  => env('HANOICHECK_WAREHOUSE_AREA'),
            'category_map'    => json_decode((string) env('HANOICHECK_CATEGORY_MAP', '{}'), true) ?: [],
        ],
    ],

];
