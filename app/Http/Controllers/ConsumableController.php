<?php

namespace App\Http\Controllers;

use App\Models\Consumable;
use App\Models\ConsumableAllocation;
use App\Models\Equipment;
use App\Models\User;
use App\Http\Requests\StoreConsumableRequest;
use App\Http\Requests\UpdateConsumableRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ConsumableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Consumable::with(["responsibleUser", "allocations"]);

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

        $consumables = $query->latest()->paginate(15)->withQueryString();

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

        return view("consumables.create", compact("users"));
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

        $data = $request->safe()->except("purchase_document");

        if ($request->hasFile("purchase_document")) {
            $data = array_merge(
                $data,
                $this->storePurchaseDocument($request, null),
            );
        }

        $consumable = Consumable::create($data);

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
            "allocations" => function ($query) {
                $query
                    ->with(["equipment", "installedByUser", "writtenOffByUser"])
                    ->latest();
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

        return view("consumables.edit", compact("consumable", "users"));
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

        $data = $request->safe()->except("purchase_document");

        if ($request->hasFile("purchase_document")) {
            $data = array_merge(
                $data,
                $this->storePurchaseDocument($request, $consumable),
            );
        }

        $consumable->update($data);

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
     * Установить расходник в оборудование.
     */
    public function install(Request $request, Consumable $consumable)
    {
        if (!Auth::check() || !Auth::user()->canManageEquipment()) {
            abort(403);
        }

        $data = $request->validate([
            "equipment_id" => "required|exists:equipment,id",
            "quantity" => "required|integer|min:1",
            "installed_at" => "nullable|date|before_or_equal:today",
            "note" => "nullable|string|max:255",
        ]);

        if ($data["quantity"] > $consumable->quantity_in_stock) {
            return back()
                ->withInput()
                ->withErrors([
                    "quantity" => "На складе недостаточно расходника (в наличии: {$consumable->quantity_in_stock})",
                ]);
        }

        $consumable->allocations()->create([
            "equipment_id" => $data["equipment_id"],
            "quantity" => $data["quantity"],
            "status" => ConsumableAllocation::STATUS_INSTALLED,
            "installed_at" => $data["installed_at"] ?? now(),
            "installed_by_user_id" => Auth::id(),
            "note" => $data["note"] ?? null,
        ]);

        return redirect()
            ->route("consumables.show", $consumable)
            ->with("success", "Расходник установлен в оборудование");
    }

    /**
     * Отменить установку (пока не списана) — количество возвращается на склад.
     */
    public function destroyAllocation(Consumable $consumable, ConsumableAllocation $allocation)
    {
        if (!Auth::check() || !Auth::user()->canManageEquipment()) {
            abort(403);
        }

        if ($allocation->consumable_id !== $consumable->id) {
            abort(404);
        }

        if (!$allocation->isInstalled()) {
            return back()->withErrors([
                "allocation" => "Списанную запись нельзя удалить",
            ]);
        }

        $allocation->delete();

        return back()->with("success", "Установка отменена, количество возвращено на склад");
    }

    /**
     * Списать расходник напрямую со склада (без установки в оборудование).
     */
    public function writeOffStock(Request $request, Consumable $consumable)
    {
        if (!Auth::check() || !Auth::user()->canManageEquipment()) {
            abort(403);
        }

        $data = $request->validate([
            "quantity" => "required|integer|min:1",
            "written_off_at" => "nullable|date|before_or_equal:today",
            "written_off_reason" => "nullable|string|max:255",
            "document" => "required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,png,jpg,jpeg,gif",
        ]);

        if ($data["quantity"] > $consumable->quantity_in_stock) {
            return back()
                ->withInput()
                ->withErrors([
                    "quantity" => "На складе недостаточно расходника (в наличии: {$consumable->quantity_in_stock})",
                ]);
        }

        $file = $request->file("document");
        $path = $file->store(
            "consumables/write-offs/{$consumable->id}",
            "local",
        );

        $consumable->allocations()->create([
            "equipment_id" => null,
            "quantity" => $data["quantity"],
            "status" => ConsumableAllocation::STATUS_WRITTEN_OFF,
            "written_off_at" => $data["written_off_at"] ?? now(),
            "written_off_by_user_id" => Auth::id(),
            "written_off_reason" => $data["written_off_reason"] ?? null,
            "write_off_document_path" => $path,
            "write_off_document_original_name" => $file->getClientOriginalName(),
            "write_off_document_mime_type" => $file->getClientMimeType(),
            "write_off_document_size" => $file->getSize(),
        ]);

        return redirect()
            ->route("consumables.show", $consumable)
            ->with("success", "Расходник списан со склада");
    }

    /**
     * Списать расходник, который был установлен в оборудование.
     */
    public function writeOffAllocation(Request $request, Consumable $consumable, ConsumableAllocation $allocation)
    {
        if (!Auth::check() || !Auth::user()->canManageEquipment()) {
            abort(403);
        }

        if ($allocation->consumable_id !== $consumable->id) {
            abort(404);
        }

        if (!$allocation->isInstalled()) {
            return back()->withErrors([
                "allocation" => "Эта запись уже списана",
            ]);
        }

        $data = $request->validate([
            "written_off_at" => "nullable|date|before_or_equal:today",
            "written_off_reason" => "nullable|string|max:255",
            "document" => "required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,png,jpg,jpeg,gif",
        ]);

        $file = $request->file("document");
        $path = $file->store(
            "consumables/write-offs/{$consumable->id}",
            "local",
        );

        $allocation->update([
            "status" => ConsumableAllocation::STATUS_WRITTEN_OFF,
            "written_off_at" => $data["written_off_at"] ?? now(),
            "written_off_by_user_id" => Auth::id(),
            "written_off_reason" => $data["written_off_reason"] ?? null,
            "write_off_document_path" => $path,
            "write_off_document_original_name" => $file->getClientOriginalName(),
            "write_off_document_mime_type" => $file->getClientMimeType(),
            "write_off_document_size" => $file->getSize(),
        ]);

        return back()->with("success", "Расходник списан");
    }

    /**
     * Скачать документ закупки расходника.
     */
    public function downloadPurchaseDocument(Consumable $consumable)
    {
        if (!$consumable->hasPurchaseDocument()) {
            abort(404);
        }

        if (!Storage::disk("local")->exists($consumable->purchase_document_path)) {
            abort(404);
        }

        return Storage::disk("local")->download(
            $consumable->purchase_document_path,
            $consumable->purchase_document_original_name,
        );
    }

    /**
     * Удалить документ закупки расходника.
     */
    public function destroyPurchaseDocument(Consumable $consumable)
    {
        if (!Auth::check() || !Auth::user()->canManageEquipment()) {
            abort(403);
        }

        if ($consumable->hasPurchaseDocument()) {
            Storage::disk("local")->delete($consumable->purchase_document_path);
        }

        $consumable->update([
            "purchase_document_path" => null,
            "purchase_document_original_name" => null,
            "purchase_document_mime_type" => null,
            "purchase_document_size" => 0,
        ]);

        return back()->with("success", "Документ закупки удалён");
    }

    /**
     * Скачать документ списания (с проверкой принадлежности расходнику).
     */
    public function downloadWriteOffDocument(Consumable $consumable, ConsumableAllocation $allocation)
    {
        if ($allocation->consumable_id !== $consumable->id) {
            abort(404);
        }

        if (!$allocation->hasWriteOffDocument()) {
            abort(404);
        }

        if (!Storage::disk("local")->exists($allocation->write_off_document_path)) {
            abort(404);
        }

        return Storage::disk("local")->download(
            $allocation->write_off_document_path,
            $allocation->write_off_document_original_name,
        );
    }

    /**
     * Сохранить файл документа закупки на приватном диске.
     */
    private function storePurchaseDocument(Request $request, ?Consumable $consumable): array
    {
        $file = $request->file("purchase_document");
        $folder = $consumable ? $consumable->id : "new";
        $path = $file->store("consumables/purchases/{$folder}", "local");

        return [
            "purchase_document_path" => $path,
            "purchase_document_original_name" => $file->getClientOriginalName(),
            "purchase_document_mime_type" => $file->getClientMimeType(),
            "purchase_document_size" => $file->getSize(),
        ];
    }
}
