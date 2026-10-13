<?php

namespace App\Http\Controllers;

use App\Http\Requests\EquipmentImportPreviewRequest;
use App\Services\EquipmentImport\EquipmentImportService;
use App\Services\EquipmentImport\ImportFileReader;
use App\Services\EquipmentImport\InvalidImportFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Массовая загрузка оборудования из CSV/JSON в два шага: сначала проверка
 * файла и показ результата, потом — запись после подтверждения.
 *
 * Загруженный файл между шагами лежит в закрытом хранилище, а в сессии —
 * только ссылка на него и выбранные настройки.
 */
class EquipmentImportController extends Controller
{
    private const DIRECTORY = "equipment-imports";
    private const SESSION_KEY = "equipment_import";

    public function __construct(
        private readonly ImportFileReader $reader,
        private readonly EquipmentImportService $importer,
    ) {
    }

    public function create(): View
    {
        return view("equipment.import", ["fields" => EquipmentImportService::FIELDS]);
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen("php://output", "w");

            // BOM и «;» — чтобы русский Excel открыл файл без «кракозябр» и в колонках.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, EquipmentImportService::FIELDS, ";", '"', "");
            fputcsv($out, [
                "2101344347", "", "Принтер Canon", "i-SENSYS LBP6030", "SN-0001", "Принтер", "working",
                "4-ка", "4-ка", "", "нет", "", "", "", "",
            ], ";", '"', "");
            fputcsv($out, [
                "2101344348", "А1-сотрудник-001", "Компьютер преподавателя", "", "", "Компьютер", "working",
                "101", "Кабинет информатики", "Windows 11 Pro", "да", "31.12.2027", "01.09.2025", "", "",
            ], ";", '"', "");

            fclose($out);
        }, "equipment-import-template.csv", ["Content-Type" => "text/csv; charset=UTF-8"]);
    }

    public function preview(EquipmentImportPreviewRequest $request): View|RedirectResponse
    {
        $upload = $request->file("file");
        $extension = strtolower($upload->getClientOriginalExtension());
        $mode = $request->input("existing");
        $createMissing = $request->boolean("create_missing");

        try {
            $file = $this->reader->read($upload->getRealPath(), $extension);
            $plan = $this->importer->plan($file, $mode, $createMissing);
        } catch (InvalidImportFile $e) {
            return back()->withInput()->withErrors(["file" => $e->getMessage()]);
        }

        $this->discardPending($request);

        $token = (string) Str::ulid();
        $path = $upload->storeAs(self::DIRECTORY, "{$token}.{$extension}");

        $request->session()->put(self::SESSION_KEY, [
            "token" => $token,
            "path" => $path,
            "extension" => $extension,
            "existing" => $mode,
            "create_missing" => $createMissing,
        ]);

        return view("equipment.import-preview", [
            "plan" => $plan,
            "token" => $token,
            "fileName" => $upload->getClientOriginalName(),
            "mode" => $mode,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(self::SESSION_KEY);

        if (
            !is_array($pending)
            || !hash_equals($pending["token"], (string) $request->input("token"))
            || !Storage::exists($pending["path"])
        ) {
            return redirect()
                ->route("equipment.import.create")
                ->withErrors(["file" => "Проверенный файл больше не доступен. Загрузите его заново."]);
        }

        try {
            // Файл читаем и проверяем ещё раз: за время между шагами данные в системе могли измениться.
            $file = $this->reader->read(Storage::path($pending["path"]), $pending["extension"]);
            $plan = $this->importer->plan($file, $pending["existing"], $pending["create_missing"]);
        } catch (InvalidImportFile $e) {
            return redirect()->route("equipment.import.create")->withErrors(["file" => $e->getMessage()]);
        }

        if ($plan->hasErrors()) {
            $this->discardPending($request);

            return redirect()
                ->route("equipment.import.create")
                ->withErrors(["file" => "Данные в системе изменились, и файл больше не проходит проверку. Загрузите его заново."]);
        }

        $result = $this->importer->apply($plan);

        $this->discardPending($request);

        return redirect()
            ->route("equipment.index")
            ->with("success", $this->summary($result));
    }

    private function discardPending(Request $request): void
    {
        $pending = $request->session()->pull(self::SESSION_KEY);

        if (is_array($pending)) {
            Storage::delete($pending["path"]);
        }
    }

    /**
     * @param array{created: int, updated: int, skipped: int, categories: int, rooms: int} $result
     */
    private function summary(array $result): string
    {
        $parts = [
            "создано {$result["created"]}",
            "обновлено {$result["updated"]}",
            "пропущено {$result["skipped"]}",
        ];

        if ($result["categories"] > 0) {
            $parts[] = "новых категорий {$result["categories"]}";
        }

        if ($result["rooms"] > 0) {
            $parts[] = "новых кабинетов {$result["rooms"]}";
        }

        return "Импорт завершён: " . implode(", ", $parts) . ".";
    }
}
