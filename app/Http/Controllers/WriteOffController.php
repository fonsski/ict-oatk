<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWriteOffRequest;
use App\Models\Equipment;
use App\Models\WriteOff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WriteOffController extends Controller
{
    /**
     * Список актов списания оборудования.
     */
    public function index(Request $request)
    {
        $query = WriteOff::withCount("items")->with("createdBy");

        if ($request->filled("status")) {
            $query->where("status", $request->input("status"));
        }

        if ($request->filled("date_from")) {
            $query->whereDate("date", ">=", $request->input("date_from"));
        }

        if ($request->filled("date_to")) {
            $query->whereDate("date", "<=", $request->input("date_to"));
        }

        $writeOffs = $query->latest("date")->paginate(15)->withQueryString();

        return view("write-offs.index", compact("writeOffs"));
    }

    /**
     * Форма нового акта. Оборудование приходит из bulk-действия в списке
     * инвентаря (чекбоксы), поэтому ids передаются GET-параметром.
     */
    public function create(Request $request)
    {
        $this->authorizeManage();

        $ids = collect(explode(",", (string) $request->input("equipment_ids")))
            ->filter()
            ->map(fn($id) => (int) $id)
            ->all();

        $equipment = Equipment::with(["status", "room", "category"])
            ->whereIn("id", $ids)
            ->get();

        if ($equipment->isEmpty()) {
            return redirect()
                ->route("equipment.index")
                ->withErrors(["equipment_ids" => "Выберите оборудование для списания"]);
        }

        return view("write-offs.create", compact("equipment"));
    }

    /**
     * Создать акт с позициями (массовое списание — основной сценарий).
     */
    public function store(StoreWriteOffRequest $request)
    {
        $this->authorizeManage();

        $data = $request->validated();

        $writeOff = DB::transaction(function () use ($data) {
            $writeOff = WriteOff::create([
                "number" => "TMP-" . uniqid(),
                "date" => $data["date"],
                "reason" => $data["reason"],
                "basis" => $data["basis"] ?? null,
                "comment" => $data["comment"] ?? null,
                "created_by_user_id" => Auth::id(),
                "status" => WriteOff::STATUS_DRAFT,
            ]);

            $writeOff->update([
                "number" => "АКТ-" . now()->format("Y") . "-" . str_pad($writeOff->id, 4, "0", STR_PAD_LEFT),
            ]);

            foreach (array_unique($data["equipment_ids"]) as $equipmentId) {
                $writeOff->items()->create(["equipment_id" => $equipmentId]);
            }

            return $writeOff;
        });

        return redirect()
            ->route("write-offs.show", $writeOff)
            ->with("success", "Акт создан. Приложите документ и проведите акт.");
    }

    public function show(WriteOff $writeOff)
    {
        $writeOff->load([
            "items.equipment.status",
            "items.equipment.room",
            "createdBy",
            "documents.uploadedBy",
        ]);

        return view("write-offs.show", compact("writeOff"));
    }

    /**
     * Провести акт: все единицы получают статус «Списано».
     */
    public function post(WriteOff $writeOff)
    {
        $this->authorizeManage();

        try {
            $writeOff->post();
        } catch (\RuntimeException $e) {
            return back()->withErrors(["write_off" => $e->getMessage()]);
        }

        return redirect()
            ->route("write-offs.show", $writeOff)
            ->with("success", "Акт проведён: оборудование переведено в статус «Списано»");
    }

    public function destroy(WriteOff $writeOff)
    {
        $this->authorizeManage();

        if (!$writeOff->isDraft()) {
            abort(403, "Удалить можно только акт в статусе «Черновик»");
        }

        $writeOff->delete();

        return redirect()
            ->route("write-offs.index")
            ->with("success", "Акт удалён");
    }

    private function authorizeManage(): void
    {
        if (
            !Auth::check() ||
            !in_array(optional(Auth::user()->role)->slug, ["admin", "master"])
        ) {
            abort(403, "Только администраторы и мастера могут списывать оборудование");
        }
    }
}
