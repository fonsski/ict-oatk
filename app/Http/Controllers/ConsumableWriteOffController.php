<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConsumableWriteOffRequest;
use App\Models\Consumable;
use App\Models\ConsumableWriteOff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConsumableWriteOffController extends Controller
{
    /**
     * Список актов списания расходников.
     */
    public function index(Request $request)
    {
        $query = ConsumableWriteOff::withCount("items")->with("writtenOffByUser");

        if ($request->filled("date_from")) {
            $query->whereDate("written_off_at", ">=", $request->input("date_from"));
        }

        if ($request->filled("date_to")) {
            $query->whereDate("written_off_at", "<=", $request->input("date_to"));
        }

        $writeOffs = $query->latest("written_off_at")->paginate(15)->withQueryString();

        return view("consumable-write-offs.index", compact("writeOffs"));
    }

    /**
     * Форма массового списания. Параметр consumable_id позволяет открыть
     * форму с уже выбранным расходником (кнопка "Списать" на его странице).
     */
    public function create(Request $request)
    {
        $this->authorizeManage();

        $consumables = Consumable::orderBy("name")->get();
        $preselectedConsumableId = $request->integer("consumable_id") ?: null;

        return view(
            "consumable-write-offs.create",
            compact("consumables", "preselectedConsumableId"),
        );
    }

    /**
     * Провести массовое списание: несколько позиций, по каждой — своё
     * количество. Всё уменьшение остатков — в одной транзакции.
     */
    public function store(StoreConsumableWriteOffRequest $request)
    {
        $this->authorizeManage();

        $data = $request->validated();

        try {
            $writeOff = DB::transaction(function () use ($data) {
                $writeOff = ConsumableWriteOff::create([
                    "number" => "TMP-" . uniqid(),
                    "written_off_at" => $data["written_off_at"],
                    "reason" => $data["reason"] ?? null,
                    "written_off_by_user_id" => Auth::id(),
                    "comment" => $data["comment"] ?? null,
                ]);
                $writeOff->update([
                    "number" => "СП-" . now()->format("Y") . "-" . str_pad($writeOff->id, 4, "0", STR_PAD_LEFT),
                ]);

                foreach ($data["items"] as $itemData) {
                    $consumable = Consumable::findOrFail($itemData["consumable_id"]);
                    $consumable->recordOutcome($itemData["quantity"], [
                        "reason" => $data["reason"] ?: "Массовое списание {$writeOff->number}",
                        "consumable_write_off_id" => $writeOff->id,
                        "moved_by_user_id" => Auth::id(),
                        "moved_at" => $data["written_off_at"],
                    ]);
                }

                return $writeOff;
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(["items" => $e->getMessage()]);
        }

        return redirect()
            ->route("consumable-write-offs.show", $writeOff)
            ->with("success", "Списание оформлено, остатки обновлены");
    }

    public function show(ConsumableWriteOff $consumableWriteOff)
    {
        $consumableWriteOff->load([
            "items.consumable",
            "writtenOffByUser",
            "documents.uploadedBy",
        ]);

        return view("consumable-write-offs.show", [
            "writeOff" => $consumableWriteOff,
        ]);
    }

    private function authorizeManage(): void
    {
        if (!Auth::check() || !Auth::user()->canManageEquipment()) {
            abort(403);
        }
    }
}
