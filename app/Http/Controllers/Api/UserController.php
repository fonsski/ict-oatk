<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    
     * Получение списка технических специалистов для назначения заявок
     *
     * @return \Illuminate\Http\JsonResponse

    public function technicians()
    {
        
        if (
            !Auth::check() ||
            !in_array(optional(Auth::user()->role)->slug, [
                "admin",
                "master",
                "technician",
            ])
        ) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        
        $technicians = User::whereHas('role', function ($query) {
                $query->whereIn('slug', ['admin', 'master', 'technician']);
            })
            ->where('active', true)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json([
            'technicians' => $technicians,
        ]);
    }
}
