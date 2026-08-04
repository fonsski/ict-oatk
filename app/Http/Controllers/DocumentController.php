<?php

namespace App\Http\Controllers;

use App\Models\ConsumableWriteOff;
use App\Models\Document;
use App\Models\Equipment;
use App\Models\Purchase;
use App\Models\WriteOff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Разрешённые сущности, к которым можно прикладывать документы.
     * Ключ — слаг в маршруте, значение — класс модели.
     */
    private const SUBJECT_MAP = [
        "equipment" => Equipment::class,
        "purchase" => Purchase::class,
        "write-off" => WriteOff::class,
        "consumable-write-off" => ConsumableWriteOff::class,
    ];

    /**
     * Общий список всех документов в системе с фильтрами.
     */
    public function index(Request $request)
    {
        $query = Document::with(["documentable", "uploadedBy"]);

        if ($request->filled("type")) {
            $query->where("type", $request->input("type"));
        }

        if ($request->filled("date_from")) {
            $query->whereDate("created_at", ">=", $request->input("date_from"));
        }

        if ($request->filled("date_to")) {
            $query->whereDate("created_at", "<=", $request->input("date_to"));
        }

        if ($request->filled("uploaded_by_user_id")) {
            $query->where(
                "uploaded_by_user_id",
                $request->input("uploaded_by_user_id"),
            );
        }

        if ($request->filled("search")) {
            $query->where(
                "original_name",
                "like",
                "%" . $request->input("search") . "%",
            );
        }

        $documents = $query->latest()->paginate(20)->withQueryString();

        return view("documents.index", compact("documents"));
    }

    /**
     * Загрузить документ и привязать к сущности.
     */
    public function store(Request $request, string $type, int $id)
    {
        if (!Auth::check() || !Auth::user()->canManageEquipment()) {
            abort(403);
        }

        $modelClass = self::SUBJECT_MAP[$type] ?? null;
        if (!$modelClass) {
            abort(404);
        }

        $documentable = $modelClass::findOrFail($id);

        $data = $request->validate([
            "file" => [
                "required",
                "file",
                "max:" . Document::MAX_SIZE_KB,
                "mimes:" . Document::ALLOWED_MIMES,
            ],
            "type" => "nullable|in:" . implode(",", array_keys(Document::TYPES)),
            "description" => "nullable|string|max:255",
        ]);

        $file = $request->file("file");
        $path = $file->store(
            "documents/{$type}/{$documentable->id}",
            "local",
        );

        $documentable->documents()->create([
            "type" => $data["type"] ?? Document::TYPE_OTHER,
            "path" => $path,
            "original_name" => $file->getClientOriginalName(),
            "mime_type" => $file->getClientMimeType(),
            "size" => $file->getSize(),
            "description" => $data["description"] ?? null,
            "uploaded_by_user_id" => Auth::id(),
        ]);

        return back()->with("success", "Документ прикреплён");
    }

    /**
     * Скачать документ (приватный диск, только через контроллер).
     */
    public function download(Document $document)
    {
        if (!Storage::disk("local")->exists($document->path)) {
            abort(404);
        }

        return Storage::disk("local")->download(
            $document->path,
            $document->original_name,
        );
    }

    /**
     * Удалить документ.
     */
    public function destroy(Document $document)
    {
        if (
            !Auth::check() ||
            !in_array(optional(Auth::user()->role)->slug, ["admin", "master"])
        ) {
            abort(403, "Только администраторы и мастера могут удалять документы");
        }

        Storage::disk("local")->delete($document->path);
        $document->delete();

        return back()->with("success", "Документ удалён");
    }
}
