<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Player\StoreBookingRequest;
use App\Http\Resources\Api\V1\PlayerBookingResource;
use App\Services\PlayerBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerBookingController extends Controller
{
    public function __construct(private readonly PlayerBookingService $playerBookingService)
    {
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $booking = $this->playerBookingService->storeBooking(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Booking confirmed successfully',
            'data' => new PlayerBookingResource($booking),
        ], 201);
    }
}
