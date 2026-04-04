<?php

namespace Modules\Product\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Product\Http\Requests\Admin\UploadProductMediaRequest;
use Modules\Product\Media\Services\ProductMediaService;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Admin - Product media upload
 */
class ProductMediaUploadController extends BaseController
{
    public function __construct(protected ProductMediaService $productMediaService) {}

    /**
     * Upload a file to staging (returns a UUID to attach later via product `media_uuids`).
     *
     * Send as `multipart/form-data`. Validation is defined in {@see UploadProductMediaRequest}.
     *
     * @bodyParam file file required Binary upload. Allowed: JPEG, PNG, WebP, GIF, MP4, WebM, QuickTime. Max ~50MB (51200 KB).
     *
     * @response array{success: true, message: string, data: array{uuid: string, mime_type: string|null, url: string}} 201
     */
    public function store(UploadProductMediaRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $file = $request->file('file');
            if (! $file) {
                return $this->errorResponse('File is required', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $payload = $this->productMediaService->uploadStaging($file, (int) $request->user()->id);

            return $this->successResponse($payload, 'File uploaded successfully', Response::HTTP_CREATED);
        });
    }
}
