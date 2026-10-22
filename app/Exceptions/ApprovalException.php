<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class ApprovalException extends RuntimeException
{
    public function __construct(string $message, private int $statusCode = 409)
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $this->getMessage(),
        ], $this->statusCode);
    }

    /**
     * Bukan error server (409/422/403 yang memang diharapkan), jadi tidak perlu masuk log.
     */
    public function report(): void {}
}
