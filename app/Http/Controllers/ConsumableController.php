<?php

namespace App\Http\Controllers;

use App\Models\Consumable;
use App\Models\Room;
use App\Models\StockMovement;
use App\Models\User;
use App\Http\Requests\StoreConsumableRequest;
use App\Http\Requests\UpdateConsumableRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConsumableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Consumable::with(["responsibleUser", "room"]);

        if ($request->filled("search")) {
            $search = $request->input("search");
            $query->where(function ($q) use ($search) {
                $q->where("name", "like", "%{$search}%")->orWhere(
                    "category",
                    "like",
                    "%{$search}%",
                );
            });
        }

        if ($request->boolean("low_stock")) {
            $query->lowStock();
        }

        $consumables = $query->orderBy("name")->paginate(15)->withQueryString();

        return view("consumables.index", compact("consumables"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!Auth::check() || !Auth::user()->canManageEquipment()) {
            abort(403);
        }

        $users = User::active()->orderBy("name")->get();
        $rooms = Room::active()->orderBy("number")->get();

        return view("consumables.create", compact("users", "rooms"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreConsumableRequest $request)
    {
        if (
            !Auth::check() ||
            !in_array(optional(Auth::user()->role)->slug, ["admin", "master"])
        ) {
            abort(
                403,
                "Только администраторы и мастера могут добавлять расходники",
            );
        }

        $data = $request->validated();
        $initialQuantity = $data["quantity"];
        unset($data["quantity"]);
        $data["quantity"] = 0;

        $consumable = Consumable::create($data);

        if ($initialQuantity > 0) {
            $consumable->recordIncome($initialQuantity, [
                "reason" => "Начальный остаток",
                "moved_by_user_id" => Auth::id(),
                "moved_at" => now(),
            ]);
        }

        return redirect()
            ->route("consumables.show", $consumable)
            ->with("success", "Расходник добавлен");
    }

    /**
     * Display the specified resource.
     */
    public function show(Consumable $consumable)
    {
        $consumable->load([
            "responsibleUser",
            "room",
            "movements" => function ($query) {
                $query
                    ->with(["equipment", "purchase", "consumableWriteOff", "movedByUser"])
                    ->latest("moved_at")
                    ->latest("id");
            },
        ]);

        return view("consumables.show", compact("consumable"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Consumable $consumable)
    {
        if (!Auth::check() || !Auth::user()->canManageEquipment()) {
            abort(403);
        }

        $users = User::active()->orderBy("name")->get();
        $rooms = Room::active()->orderBy("number")->get();

        return view("consumables.edit", compact("consumable", "users", "rooms"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateConsumableRequest $request, Consumable $consumable)
    {
        if (
            !Auth::check() ||
            !in_array(optional(Auth::user()->role)->slug, ["admin", "master"])
        ) {
            abort(
                403,
                "Только администраторы и мастера могут изменять расходники",
            );
        }

        $consumable->update($request->validated());

        return redirect()
            ->route("consumables.show", $consumable)
            ->with("success", "Расходник обновлён");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Consumable $consumable)
    {
        if (
            !Auth::check() ||
            !in_array(optional(Auth::user()->role)->slug, ["admin", "master"])
        ) {
            abort(
                403,
                "Только администраторы и мастера могут удалять расходники",
            );
        }

        $consumable->delete();

        return redirect()
            ->route("consumables.index")
            ->with("success", "Расходник удалён");
    }

    /**
     * Выдать/установить расходник в оборудование (расход со склада).
     */
    public function issue(Request $request, Consumable $consumable)
    {
        if (!Auth::check() || !Auth::user()->canManageEquipment()) {
            abort(403);
        }

        $data = $request->validate([
            "equipment_id" => "required|exists:equipment,id",
            "quantity" => "required|integer|min:1",
            "moved_at" => "nullable|date|before_or_equal:today",
            "reason" => "nullable|string|max:255",
        ]);

        try {
            $consumable->recordOutcome($data["quantity"], [
                "equipment_id" => $data["equipment_id"],
                "reason" => ($data["reason"] ?? null) ?: "Выдано/установлено в оборудование",
                "moved_by_user_id" => Auth::id(),
                "moved_at" => $data["moved_at"] ?? now(),
            ]);
        } catch (\RuntimeException $e) {
            return back()->withErrors(["quantity" => $e->getMessage()]);
        }

        return redirect()
            ->route("consumables.show", $consumable)
            ->with("success", "Расходник выдан в оборудование");
    }

    /**
     * Отменить выдачу в оборудование (пока она не часть акта списания) —
     * количество возвращается на склад отдельным движением прихода.
     */
    public function destroyMovement(Consumable $consumable, StockMovement $movement)
    {
        if (!Auth::check() || !Auth::user()->canManageEquipment()) {
            abort(403);
        }

        if ($movement->consumable_id !== $consumable->id) {
            abort(404);
        }

        if (!$movement->isOutcome() || $movement->consumable_write_off_id) {
            return back()->withErrors([
                "movement" => "Эту запись нельзя отменить",
            ]);
        }

        $consumable->recordIncome($movement->quantity, [
            "reason" => "Отмена выдачи (движение №{$movement->id})",
            "moved_by_user_id" => Auth::id(),
            "moved_at" => now(),
        ]);
        $movement->delete();

        return back()->with(
            "success",
            "Выдача отменена, количество возвращено на склад",
        );
    }
}
