<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Получение списка технических специалистов для назначения заявок
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function technicians(Request $request)
    {
        // Проверка аутентификации и роли
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

        // Получение пользователей с ролями admin, master, technician.
        // Колонка активности называется is_active — по несуществующей
        // "active" запрос падал, и список исполнителей не открывался.
        $technicians = User::whereHas('role', function ($query) {
                $query->whereIn('slug', ['admin', 'master', 'technician']);
            })
            ->where('is_active', true)
            // Того, кто уже назначен на заявку, в списке быть не должно:
            // выбирать его повторно незачем.
            ->when(
                $request->filled('exclude'),
                fn ($query) => $query->whereKeyNot($request->integer('exclude')),
            )
            ->with('role:id,slug,name')
            ->select('id', 'name', 'email', 'position', 'role_id')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'position' => $user->position,
                'role' => $user->role->name ?? null,
            ]);

        return response()->json([
            'technicians' => $technicians,
        ]);
    }
}
