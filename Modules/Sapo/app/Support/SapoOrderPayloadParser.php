<?php

namespace Modules\Sapo\Support;

class SapoOrderPayloadParser
{
    private const SERIAL_KEYS = ['serial_numbers', 'serials', 'imei', 'imeis'];

    public function __construct(
        private readonly array $payload,
    ) {}

    public function orderCode(): ?string
    {
        $value = $this->payload['name'] ?? $this->payload['order_number'] ?? $this->payload['id'] ?? null;

        return $value !== null ? (string) $value : null;
    }

    public function orderedAt(): ?string
    {
        return $this->payload['created_on'] ?? $this->payload['created_at'] ?? null;
    }

    public function totalAmount(): ?float
    {
        $value = $this->payload['total_price'] ?? null;

        return $value !== null ? (float) $value : null;
    }

    public function customerName(): ?string
    {
        $customer = $this->payload['customer'] ?? [];
        $name     = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));

        return $name !== '' ? $name : null;
    }

    public function customerPhone(): ?string
    {
        $customer = $this->payload['customer'] ?? [];

        return $customer['phone'] ?? null;
    }

    public function customerEmail(): ?string
    {
        return $this->payload['customer']['email'] ?? null;
    }

    public function customerExternalId(): ?string
    {
        $value = $this->payload['customer']['id'] ?? null;

        return $value !== null ? (string) $value : null;
    }

    /** @return string[] */
    public function serialCodes(): array
    {
        $codes = [];

        foreach ($this->payload['line_items'] ?? [] as $lineItem) {
            foreach (self::SERIAL_KEYS as $key) {
                if (! empty($lineItem[$key]) && is_array($lineItem[$key])) {
                    $codes = array_merge($codes, $lineItem[$key]);
                }
            }
        }

        return array_values(array_unique(array_filter($codes)));
    }
}
