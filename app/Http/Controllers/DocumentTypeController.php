<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentTypeController extends Controller
{
    public function index()
    {
        $types = DocumentType::ordered()->get();

        // Сколько документов каждого типа — чтобы показать и запретить удаление.
        $usage = Document::selectRaw("type, count(*) as c")
            ->groupBy("type")
            ->pluck("c", "type");

        return view("document-types.index", compact("types", "usage"));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            "name" => "required|string|max:100",
        ]);

        $slug = Str::slug($data["name"], "_");
        if ($slug === "") {
            $slug = "type_" . Str::random(6);
        }

        // Уникальность slug.
        $base = $slug;
        $i = 2;
        while (DocumentType::where("slug", $slug)->exists()) {
            $slug = $base . "_" . $i++;
        }

        DocumentType::create([
            "name" => $data["name"],
            "slug" => $slug,
            "sort_order" => (int) DocumentType::max("sort_order") + 1,
        ]);

        return redirect()
            ->route("document-types.index")
            ->with("success", "Тип документа добавлен");
    }

    public function update(Request $request, DocumentType $documentType)
    {
        $data = $request->validate([
            "name" => "required|string|max:100",
        ]);

        $documentType->update(["name" => $data["name"]]);

        return redirect()
            ->route("document-types.index")
            ->with("success", "Тип документа переименован");
    }

    public function destroy(DocumentType $documentType)
    {
        $inUse = Document::where("type", $documentType->slug)->exists();
        if ($inUse) {
            return back()->with(
                "error",
                "Нельзя удалить тип: он используется в документах.",
            );
        }

        $documentType->delete();

        return back()->with("success", "Тип документа удалён");
    }
}
