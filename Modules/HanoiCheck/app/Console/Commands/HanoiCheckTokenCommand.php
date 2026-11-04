<?php

namespace Modules\HanoiCheck\Console\Commands;

use Illuminate\Console\Command;
use Modules\HanoiCheck\Services\HanoiCheckClient;
use Modules\HanoiCheck\Services\HanoiCheckTokenService;
use Throwable;

class HanoiCheckTokenCommand extends Command
{
    protected $signature = 'hanoicheck:token {--fresh : Xóa token đang cache và xin cấp mới}';

    protected $description = 'Kiểm tra lấy mã truy cập HanoiCheck (POST /api/supplier/token)';

    public function handle(HanoiCheckTokenService $tokens): int
    {
        if (HanoiCheckClient::fake()) {
            $this->warn('HANOICHECK_FAKE=true: không gọi HTTP. Đặt HANOICHECK_FAKE=false để thử thật.');
        }

        if ($this->option('fresh')) {
            $tokens->forgetAll();
        }

        try {
            $token = $tokens->token();
        } catch (Throwable $e) {
            $this->error('Lấy token thất bại: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info('Lấy token thành công: ' . substr($token, 0, 12) . '… (' . strlen($token) . ' ký tự)');

        return self::SUCCESS;
    }
}
