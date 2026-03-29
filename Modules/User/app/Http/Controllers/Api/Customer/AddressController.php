<?php

namespace Modules\User\Http\Controllers\Api\Customer;

use App\Http\Controllers\Api\BaseController;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\User\Http\Requests\Customer\StoreAddressRequest;
use Modules\User\Http\Requests\Customer\UpdateAddressRequest;
use Modules\User\Http\Resources\AddressResource;

/**
 * @tags Customer - Address Management
 */
class AddressController extends BaseController
{
    /**
     * List current user's addresses.
     *
     * @response array{data: AddressResource[], message: string}
     */
    public function index(Request $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $addresses = $request->user()
                ->addresses()
                ->orderBy('is_default', 'desc')
                ->get();

            return $this->successResponse(AddressResource::collection($addresses), 'Addresses retrieved successfully');
        });
    }

    /**
     * Store a new address.
     *
     * @bodyParam label string required Address label (Home, Work, Other). Example: "Home"
     * @bodyParam is_default boolean Set as default address. Example: true
     * @bodyParam line_1 string required Street address. Example: "123 Main St"
     * @bodyParam line_2 string Apartment/Suite. Example: "Apt 4B"
     * @bodyParam city string required City. Example: "New York"
     * @bodyParam state string required State code (2 letters). Example: "NY"
     * @bodyParam postal_code string required ZIP code. Example: "10001"
     * @bodyParam country_code string Country code (2 letters). Example: "US"
     *
     * @response array{data: AddressResource, message: string} 201
     */
    public function store(StoreAddressRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $user = $request->user();
            $data = $request->validated();
            $data['user_id'] = $user->id;

            $address = DB::transaction(function () use ($user, $data) {
                // Check if first address inside transaction to prevent race condition
                $isFirst = ! $user->addresses()->exists();

                // If this is the first address, make it default
                if ($isFirst) {
                    $data['is_default'] = true;
                }

                // If setting as default, unset other defaults
                if ($data['is_default'] ?? false) {
                    $user->addresses()->update(['is_default' => false]);
                }

                return Address::create($data);
            });

            return $this->successResponse(new AddressResource($address), 'Address created successfully', 201);
        });
    }

    /**
     * Show address details (only if owned by current user).
     *
     * @urlParam address integer required Address ID. Example: 1
     *
     * @response array{data: AddressResource, message: string}
     */
    public function show(Request $request, Address $address): JsonResponse
    {
        return $this->execute(function () use ($request, $address): JsonResponse {
            if ($address->user_id !== $request->user()->id) {
                return $this->errorResponse('Unauthorized', 403);
            }

            return $this->successResponse(new AddressResource($address), 'Address retrieved successfully');
        });
    }

    /**
     * Update address (only if owned by current user).
     *
     * @urlParam address integer required Address ID. Example: 1
     *
     * @bodyParam label string Address label (Home, Work, Other). Example: "Work"
     * @bodyParam is_default boolean Set as default address. Example: false
     * @bodyParam line_1 string Street address. Example: "456 Oak Ave"
     * @bodyParam line_2 string Apartment/Suite. Example: "Suite 100"
     * @bodyParam city string City. Example: "Los Angeles"
     * @bodyParam state string State code (2 letters). Example: "CA"
     * @bodyParam postal_code string ZIP code. Example: "90210"
     * @bodyParam country_code string Country code (2 letters). Example: "US"
     *
     * @response array{data: AddressResource, message: string}
     */
    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        return $this->execute(function () use ($request, $address): JsonResponse {
            if ($address->user_id !== $request->user()->id) {
                return $this->errorResponse('Unauthorized', 403);
            }

            $data = $request->validated();

            // If setting as default, unset other defaults for this user
            if ($data['is_default'] ?? false) {
                $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            }

            $address->update($data);

            return $this->successResponse(new AddressResource($address), 'Address updated successfully');
        });
    }

    /**
     * Delete address (only if owned by current user).
     *
     * @urlParam address integer required Address ID. Example: 1
     *
     * @response array{data: null, message: string}
     */
    public function destroy(Request $request, Address $address): JsonResponse
    {
        return $this->execute(function () use ($request, $address): JsonResponse {
            if ($address->user_id !== $request->user()->id) {
                return $this->errorResponse('Unauthorized', 403);
            }

            $wasDefault = $address->is_default;
            $user = $request->user();

            $address->delete();

            // If deleted address was default, promote another to default (oldest first)
            if ($wasDefault) {
                $newDefault = $user->addresses()->orderBy('created_at', 'asc')->first();
                if ($newDefault) {
                    $newDefault->update(['is_default' => true]);
                }
            }

            return $this->successResponse(null, 'Address deleted successfully');
        });
    }

    /**
     * Set address as default.
     *
     * @urlParam address integer required Address ID. Example: 1
     *
     * @response array{data: AddressResource, message: string}
     */
    public function setDefault(Request $request, Address $address): JsonResponse
    {
        return $this->execute(function () use ($request, $address): JsonResponse {
            if ($address->user_id !== $request->user()->id) {
                return $this->errorResponse('Unauthorized', 403);
            }

            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            $address->update(['is_default' => true]);

            return $this->successResponse(new AddressResource($address), 'Address set as default');
        });
    }
}
