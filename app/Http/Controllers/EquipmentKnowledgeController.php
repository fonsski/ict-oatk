<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\KnowledgeBase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EquipmentKnowledgeController extends Controller
{
    /**
     * Привязать статью базы знаний к оборудованию.
     */
    public function store(Request $request, Equipment $equipment)
    {
        $this->authorizeManage();

        $data = $request->validate([
            "knowledge_base_id" => "required|exists:knowledge_bases,id",
        ], [
            "knowledge_base_id.required" => "Выберите статью базы знаний",
            "knowledge_base_id.exists" => "Выбранная статья не найдена",
        ]);

        // syncWithoutDetaching не создаст дубль, если связь уже есть.
        $equipment->knowledgeArticles()->syncWithoutDetaching([
            $data["knowledge_base_id"],
        ]);

        return back()->with("success", "Статья привязана к оборудованию");
    }

    /**
     * Отвязать статью от оборудования.
     */
    public function destroy(Equipment $equipment, KnowledgeBase $knowledge)
    {
        $this->authorizeManage();

        $equipment->knowledgeArticles()->detach($knowledge->id);

        return back()->with("success", "Статья отвязана от оборудования");
    }

    /**
     * Поиск статей для привязки (автодополнение в карточке оборудования).
     */
    public function search(Request $request, Equipment $equipment): JsonResponse
    {
        $search = trim((string) $request->input("q"));

        $query = KnowledgeBase::query()
            ->select("id", "title", "slug")
            ->whereNotIn("id", $equipment->knowledgeArticles()->pluck("knowledge_bases.id"));

        if ($search !== "") {
            $query->where("title", "like", "%{$search}%");
        }

        return response()->json([
            "data" => $query->orderBy("title")->limit(10)->get(),
        ]);
    }

    private function authorizeManage(): void
    {
        if (!Auth::check() || !Auth::user()->canManageEquipment()) {
            abort(403);
        }
    }
}
