<?php

namespace Modules\SalesOrder\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class PrintModeRequiredException extends RuntimeException
{
    /** @param array<string, array{printed: int, printed_groups: array}> $prefill */
    public function __construct(
        public readonly array $prefill,
        public readonly string $field,
        string $message,
    ) {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message'       => $this->getMessage(),
            'errors'        => [$this->field => [$this->getMessage()]],
            'print_prefill' => $this->prefill,
        ], 422);
    }
}
