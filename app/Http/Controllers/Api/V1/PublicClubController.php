<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PublicClubResource;
use App\Models\User;
use App\Support\ApiErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PublicClubController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'sort' => ['nullable', 'string', 'in:name,-name,created_at,-created_at'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'error_code' => ApiErrorCode::VALIDATION_ERROR,
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $status = $request->input('status', 'active');
        $perPage = (int) $request->input('per_page', 20);

        $query = User::query()
            ->where('role', 'club')
            ->where('status', $status);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('club_name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $sort = $request->input('sort');
        if ($sort) {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');
            // Map 'name' query param to database column 'club_name'
            if ($field === 'name') {
                $field = 'club_name';
            }
            $query->orderBy($field, $direction);
        } else {
            // Default sort: name ascending
            $query->orderBy('club_name', 'asc');
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Clubs retrieved successfully.',
            'data' => PublicClubResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }
}
