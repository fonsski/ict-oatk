<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Models\Consumable;
use App\Models\EquipmentCategory;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    /**
     * Список закупок с фильтрами: период, поставщик, сумма.
     */
    public function index(Request $request)
    {
        $query = Purchase::query();

        if ($request->filled("date_from")) {
            $query->whereDate("date", ">=", $request->input("date_from"));
        }

        if ($request->filled("date_to")) {
            $query->whereDate("date", "<=", $request->input("date_to"));
        }

        if ($request->filled("supplier")) {
            $query->where("supplier", "like", "%" . $request->input("supplier") . "%");
        }

        if ($request->filled("sum_from")) {
            $query->where("total_sum", ">=", $request->input("sum_from"));
        }

        if ($request->filled("sum_to")) {
            $query->where("total_sum", "<=", $request->input("sum_to"));
        }

        if ($request->filled("status")) {
            $query->where("status", $request->input("status"));
        }

        $purchases = $query->latest("date")->paginate(15)->withQueryString();

        return view("purchases.index", compact("purchases"));
    }

    public function create()
    {
        $this->authorizeManage();

        $consumables = Consumable::orderBy("name")->get();
        $categories = EquipmentCategory::orderBy("name")->get();

        return view("purchases.create", compact("consumables", "categories"));
    }

    public function store(StorePurchaseRequest $request)
    {
        $this->authorizeManage();

        $data = $request->validated();

        $purchase = DB::transaction(function () use ($data) {
            $purchase = Purchase::create([
                "number" => $data["number"],
                "date" => $data["date"],
                "supplier" => $data["supplier"],
                "comment" => $data["comment"] ?? null,
                "created_by_user_id" => Auth::id(),
                "status" => Purchase::STATUS_DRAFT,
            ]);

            $this->syncItems($purchase, $data["items"]);
            $purchase->recalculateTotal();

            return $purchase;
        });

        return redirect()
            ->route("purchases.show", $purchase)
            ->with("success", "Закупка создана");
    }

    public function show(Purchase $purchase)
    {
        $purchase->load([
            "items.consumable",
            "items.equipmentCategory",
            "createdBy",
            "documents.uploadedBy",
        ]);

        return view("purchases.show", compact("purchase"));
    }

    public function edit(Purchase $purchase)
    {
        $this->authorizeManage();

        if (!$purchase->isDraft()) {
            abort(403, "Редактировать можно только закупку в статусе «Черновик»");
        }

        $purchase->load("items");
        $consumables = Consumable::orderBy("name")->get();
        $categories = EquipmentCategory::orderBy("name")->get();

        return view("purchases.edit", compact("purchase", "consumables", "categories"));
    }

    public function update(UpdatePurchaseRequest $request, Purchase $purchase)
    {
        $this->authorizeManage();

        if (!$purchase->isDraft()) {
            abort(403, "Редактировать можно только закупку в статусе «Черновик»");
        }

        $data = $request->validated();

        DB::transaction(function () use ($purchase, $data) {
            $purchase->update([
                "number" => $data["number"],
                "date" => $data["date"],
                "supplier" => $data["supplier"],
                "comment" => $data["comment"] ?? null,
            ]);

            $purchase->items()->delete();
            $this->syncItems($purchase, $data["items"]);
            $purchase->recalculateTotal();
        });

        return redirect()
            ->route("purchases.show", $purchase)
            ->with("success", "Закупка обновлена");
    }

    public function destroy(Purchase $purchase)
    {
        $this->authorizeManage();

        if (!$purchase->isDraft()) {
            abort(403, "Удалить можно только закупку в статусе «Черновик»");
        }

        $purchase->delete();

        return redirect()
            ->route("purchases.index")
            ->with("success", "Закупка удалена");
    }

    /**
     * Провести закупку: пополнить инвентарь/остатки.
     */
    public function post(Purchase $purchase)
    {
        $this->authorizeManage();

        try {
            $purchase->post();
        } catch (\RuntimeException $e) {
            return back()->withErrors(["purchase" => $e->getMessage()]);
        }

        return redirect()
            ->route("purchases.show", $purchase)
            ->with("success", "Закупка проведена: инвентарь и остатки обновлены");
    }

    private function authorizeManage(): void
    {
        if (
            !Auth::check() ||
            !in_array(optional(Auth::user()->role)->slug, ["admin", "master"])
        ) {
            abort(403, "Только администраторы и мастера могут управлять закупками");
        }
    }

    private function syncItems(Purchase $purchase, array $items): void
    {
        foreach ($items as $itemData) {
            $isConsumable = $itemData["item_type"] === "consumable";
            $sum = $itemData["quantity"] * $itemData["unit_price"];

            $purchase->items()->create([
                "item_type" => $itemData["item_type"],
                "consumable_id" => $isConsumable ? $itemData["consumable_id"] : null,
                "equipment_category_id" => $isConsumable
                    ? null
                    : ($itemData["equipment_category_id"] ?? null),
                "name" => $itemData["name"],
                "quantity" => $itemData["quantity"],
                "unit_price" => $itemData["unit_price"],
                "sum" => $sum,
            ]);
        }
    }
}
