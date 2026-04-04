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
