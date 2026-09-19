<?php

namespace Modules\LabelTemplate\Support;

readonly class LabelTemplatePreview
{
    private function __construct(
        public ?string $html,
        public ?string $errorTitle,
        public ?string $errorMessage,
    ) {}

    public static function rendered(string $html): self
    {
        return new self($html, null, null);
    }

    public static function failed(string $title, string $message): self
    {
        return new self(null, $title, $message);
    }

    public function isOk(): bool
    {
        return $this->html !== null;
    }
}
