<?php

namespace App\Service;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Response
{
    private function defaultResponse($data, $status, $message): JsonResponse
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public function ApiResponse(array $data): JsonResponse
    {
        $status = $data['status'] ?? 200;
        if (!empty($data['status'])) {
            unset($data['status']);
        }
        $message = $data['message'] ?? 'عملیات با موفقیت انجام شد.';
        if (!empty($data['message'])) {
            unset($data['message']);
        }
        return $this->defaultResponse($data, $status, $message);
    }

    public function ApiPaginatedResponse($paginator, $status = 200,$extraFields = []): JsonResponse
    {
        $response = [
            'status' => $status,
            'message' => 'عملیات با موفقیت انجام شد',
            'data' => [
                'items' => $paginator->items(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];

        if (!empty($extraFields)) {
            $response['extra_fields'] = $extraFields;
        }
        return response()->json($response);
    }
}
