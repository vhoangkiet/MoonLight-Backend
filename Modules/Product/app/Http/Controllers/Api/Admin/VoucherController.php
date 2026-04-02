<?php

namespace Modules\Product\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Product\Http\Requests\Admin\StoreVoucherRequest;
use Modules\Product\Http\Requests\Admin\UpdateVoucherRequest;
use Modules\Product\Http\Resources\VoucherResource;
use Modules\Product\Services\VoucherService;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Admin - Voucher Management
 */
class VoucherController extends BaseController
{
    public function __construct(protected VoucherService $voucherService) {}

    /**
     * List vouchers with filters.
     */
    public function index(): JsonResponse
    {
        return $this->execute(function (): JsonResponse {
            $validated = validator(request()->all(), [
                'search' => ['nullable', 'string', 'max:255'],
                'is_active' => ['nullable', 'boolean'],
                'sort_by' => ['nullable', 'string', 'in:code,name,value,valid_from,valid_until,created_at'],
                'sort_order' => ['nullable', 'string', 'in:asc,desc'],
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            ])->validate();

            $filters = [
                'search' => $validated['search'] ?? null,
                'is_active' => $validated['is_active'] ?? null,
                'sort_by' => $validated['sort_by'] ?? 'created_at',
                'sort_order' => $validated['sort_order'] ?? 'desc',
            ];

            $vouchers = $this->voucherService->getVouchers($filters, $validated['per_page'] ?? 15);

            return $this->successResponse(
                VoucherResource::collection($vouchers),
                'Vouchers retrieved successfully'
            );
        });
    }

    /**
     * Store new voucher.
     */
    public function store(StoreVoucherRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $voucher = $this->voucherService->createVoucher($request->validated());

            return $this->successResponse(
                new VoucherResource($voucher),
                'Voucher created successfully',
                Response::HTTP_CREATED
            );
        });
    }

    /**
     * Show voucher.
     */
    public function show(int $id): JsonResponse
    {
        return $this->execute(function () use ($id): JsonResponse {
            $voucher = $this->voucherService->find($id);

            if (! $voucher) {
                return $this->errorResponse('Voucher not found', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new VoucherResource($voucher),
                'Voucher retrieved successfully'
            );
        });
    }

    /**
     * Update voucher.
     */
    public function update(UpdateVoucherRequest $request, int $id): JsonResponse
    {
        return $this->execute(function () use ($request, $id): JsonResponse {
            $updated = $this->voucherService->updateVoucher($id, $request->validated());

            if (! $updated) {
                return $this->errorResponse('Voucher not found', Response::HTTP_NOT_FOUND);
            }

            $voucher = $this->voucherService->find($id);

            return $this->successResponse(
                new VoucherResource($voucher),
                'Voucher updated successfully'
            );
        });
    }

    /**
     * Delete voucher.
     */
    public function destroy(int $id): JsonResponse
    {
        return $this->execute(function () use ($id): JsonResponse {
            $deleted = $this->voucherService->deleteVoucher($id);

            if (! $deleted) {
                return $this->errorResponse('Voucher not found', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(null, 'Voucher deleted successfully');
        });
    }
}
