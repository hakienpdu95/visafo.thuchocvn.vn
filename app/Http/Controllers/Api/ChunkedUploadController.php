<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Media\ChunkedUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChunkedUploadController extends Controller
{
    public function __construct(
        private readonly ChunkedUploadService $chunkService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'upload_id'    => ['required', 'uuid'],
            'chunk_index'  => ['required', 'integer', 'min:0'],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:1000'],
            'file_name'    => ['required', 'string', 'max:255'],
            'file_size'    => ['required', 'integer', 'min:0'],
            'chunk'        => ['required', 'file'],
        ]);

        $token = $this->chunkService->appendChunk(
            $request->string('upload_id')->toString(),
            $request->file('chunk'),
            $request->integer('chunk_index'),
            $request->integer('total_chunks'),
            $request->string('file_name')->toString(),
            $request->integer('file_size'),
        );

        return response()->json(['done' => $token !== null, 'token' => $token]);
    }
}
