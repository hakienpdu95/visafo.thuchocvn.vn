<?php

namespace Modules\HanoiCheckOrder\Actions\Backend;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\HanoiCheck\Services\HanoiCheckClient;
use Modules\HanoiCheckOrder\Data\Requests\SaveHanoiCheckTransporterData;
use Modules\HanoiCheckOrder\Jobs\SyncHanoiCheckTransporterJob;
use Modules\HanoiCheckOrder\Models\HanoiCheckTransporter;

class SaveHanoiCheckTransporterAction
{
    use AsAction;

    public function handle(?HanoiCheckTransporter $transporter, SaveHanoiCheckTransporterData $data): HanoiCheckTransporter
    {
        $transporter ??= new HanoiCheckTransporter();
        $code = trim((string) $data->code);

        if ($transporter->exists) {
            $code = $transporter->code;
        } elseif ($code === '') {
            do {
                $code = 'GH-' . strtoupper(Str::random(6));
            } while (HanoiCheckTransporter::query()->where('code', $code)->exists());
        } elseif (HanoiCheckTransporter::query()->where('code', $code)->exists()) {
            throw ValidationException::withMessages(['code' => "Mã nhân sự \"{$code}\" đã tồn tại."]);
        }

        $transporter->fill([
            'code'           => $code,
            'name'           => trim($data->name),
            'license_plate'  => trim($data->license_plate),
            'phone'          => trim((string) $data->phone) ?: null,
            'cccd'           => trim((string) $data->cccd) ?: null,
            'date_of_birth'  => $data->date_of_birth ?: null,
            'address'        => trim((string) $data->address) ?: null,
            'transport_mean' => trim((string) $data->transport_mean) ?: null,
            'is_active'      => $data->is_active,
        ])->save();

        if (HanoiCheckClient::enabled()) {
            SyncHanoiCheckTransporterJob::dispatch($transporter->id);
        }

        return $transporter;
    }
}
