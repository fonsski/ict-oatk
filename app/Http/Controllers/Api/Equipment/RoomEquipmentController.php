<?php

namespace App\Http\Controllers\Api\Equipment;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoomEquipmentController extends Controller
{
    
     * Get equipment items for a specific room
     *
     * @param Request $request
     * @return JsonResponse

    public function getByRoom(Request $request): JsonResponse
    {
        $roomId = $request->input('room_id');

        if (!$roomId) {
            return response()->json([
                'success' => false,
                'message' => 'Room ID is required',
                'data' => []
            ], 400);
        }

        $equipment = Equipment::where('room_id', $roomId)
            ->select('id', 'name', 'inventory_number')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $equipment
        ]);
    }
}
