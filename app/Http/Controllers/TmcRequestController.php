<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\TmcRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\TemplateProcessor;

class TmcRequestController extends Controller
{
    public function index()
    {
        $requests = TmcRequest::with("createdBy")
            ->withCount("items")
            ->latest()
            ->paginate(20);

        return view("tmc-requests.index", compact("requests"));
    }

    public function create()
    {
        return view("tmc-requests.create");
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $tmc = TmcRequest::create([
            "number" => $data["number"] ?? null,
            "date" => $data["date"],
            "purpose" => $data["purpose"] ?? null,
            "created_by_user_id" => Auth::id(),
        ]);
        $this->syncItems($tmc, $data["items"]);
        $tmc->recalculateTotal();

        return redirect()
            ->route("tmc-requests.show", $tmc)
            ->with("success", "Заявка ТМЦ создана");
    }

    public function show(TmcRequest $tmcRequest)
    {
        $tmcRequest->load("items", "createdBy", "documents");

        return view("tmc-requests.show", ["request" => $tmcRequest]);
    }

    public function edit(TmcRequest $tmcRequest)
    {
        $tmcRequest->load("items");

        return view("tmc-requests.edit", ["request" => $tmcRequest]);
    }

    public function update(Request $request, TmcRequest $tmcRequest)
    {
        $data = $this->validateData($request);
        $tmcRequest->update([
            "number" => $data["number"] ?? null,
            "date" => $data["date"],
            "purpose" => $data["purpose"] ?? null,
        ]);
        $tmcRequest->items()->delete();
        $this->syncItems($tmcRequest, $data["items"]);
        $tmcRequest->recalculateTotal();

        return redirect()
            ->route("tmc-requests.show", $tmcRequest)
            ->with("success", "Заявка ТМЦ обновлена");
    }

    public function destroy(TmcRequest $tmcRequest)
    {
        $tmcRequest->delete();

        return redirect()
            ->route("tmc-requests.index")
            ->with("success", "Заявка ТМЦ удалена");
    }

    /**
     * Сформировать .docx по шаблону, сохранить в «Документы» и отдать на скачивание.
     */
    public function document(TmcRequest $tmcRequest)
    {
        $tmcRequest->load("items", "createdBy");

        $template = new TemplateProcessor(
            resource_path("templates/purchase_request.docx"),
        );

        $items = $tmcRequest->items;
        $rows = max($items->count(), 1);
        $template->cloneRow("np", $rows);

        for ($n = 1; $n <= $rows; $n++) {
            $item = $items[$n - 1] ?? null;
            $template->setValue("np#{$n}", $item ? (string) $n : "");
            $template->setValue("name#{$n}", $item ? $item->name : "");
            $template->setValue("qty#{$n}", $item ? (string) $item->quantity : "");
            $template->setValue("unit#{$n}", $item ? ($item->unit ?: "шт.") : "");
            $template->setValue(
                "price#{$n}",
                $item ? number_format((float) $item->unit_price, 2, ",", " ") : "",
            );
            $template->setValue(
                "sum#{$n}",
                $item ? number_format((float) $item->sum, 2, ",", " ") : "",
            );
        }

        $template->setValue(
            "total",
            number_format((float) $tmcRequest->total_sum, 2, ",", " "),
        );
        $template->setValue("author", $tmcRequest->createdBy->name ?? "");
        $template->setValue("purpose", $tmcRequest->purpose ?? "");

        $tmpFile = tempnam(sys_get_temp_dir(), "tmc_request_");
        $template->saveAs($tmpFile);

        $fileName =
            "Заявка ТМЦ " .
            ($tmcRequest->number ?: $tmcRequest->id) .
            ".docx";

        // Сохраняем в «Документы» — приватно (составивший + управляющие).
        $previous = $tmcRequest
            ->documents()
            ->where("original_name", $fileName)
            ->get();
        foreach ($previous as $doc) {
            Storage::disk("local")->delete($doc->path);
            $doc->delete();
        }

        $storedPath =
            "documents/tmc-request/{$tmcRequest->id}/" . Str::random(20) . ".docx";
        Storage::disk("local")->put($storedPath, file_get_contents($tmpFile));

        $tmcRequest->documents()->create([
            "type" => Document::TYPE_OTHER,
            "path" => $storedPath,
            "original_name" => $fileName,
            "mime_type" =>
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "size" => filesize($tmpFile),
            "description" => "Заявка на приобретение ТМЦ",
            "is_private" => true,
            "uploaded_by_user_id" =>
                $tmcRequest->created_by_user_id ?? Auth::id(),
        ]);

        return response()
            ->download($tmpFile, $fileName)
            ->deleteFileAfterSend(true);
    }

    private function validateData(Request $request): array
    {
        return $request->validate(
            [
                "number" => "nullable|string|max:50",
                "date" => "required|date",
                "purpose" => "nullable|string|max:2000",
                "items" => "required|array|min:1",
                "items.*.name" => "required|string|max:255",
                "items.*.quantity" => "required|integer|min:1",
                "items.*.unit" => "nullable|string|max:32",
                "items.*.unit_price" => "required|numeric|min:0",
            ],
            [
                "items.required" => "Добавьте хотя бы одну позицию",
                "items.*.name.required" => "Укажите наименование позиции",
                "items.*.quantity.required" => "Укажите количество",
                "items.*.unit_price.required" => "Укажите цену за единицу",
            ],
        );
    }

    private function syncItems(TmcRequest $tmc, array $items): void
    {
        foreach ($items as $item) {
            $tmc->items()->create([
                "name" => $item["name"],
                "quantity" => $item["quantity"],
                "unit" => trim($item["unit"] ?? "") ?: "шт.",
                "unit_price" => $item["unit_price"],
                "sum" => $item["quantity"] * $item["unit_price"],
            ]);
        }
    }
}
