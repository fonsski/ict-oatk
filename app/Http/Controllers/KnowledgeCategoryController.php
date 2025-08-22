<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KnowledgeCategoryController extends Controller
{
    /**
     * Конструктор контроллера
     */
    public function __construct()
    {
        // Проверка роли для всех методов
        $this->middleware(function ($request, $next) {
            if (
                !auth()->check() ||
                !auth()
                    ->user()
                    ->hasRole(["admin", "master", "technician"])
            ) {
                abort(
                    403,
                    "У вас нет прав для доступа к категориям базы знаний.",
                );
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = KnowledgeCategory::withCount("knowledgeBase")
            ->ordered()
            ->get();

        return view("knowledge.categories.index", compact("categories"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("knowledge.categories.create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            "name" => "required|string|max:255",
            "description" => "nullable|string|max:500",
            "icon" => "nullable|string|max:100",
            "color" =>
                'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            "sort_order" => "nullable|integer|min:0",
            "is_active" => "boolean",
        ]);

        $data["slug"] = Str::slug($data["name"]);
        $data["is_active"] = $request->has("is_active");
        $data["sort_order"] = $data["sort_order"] ?? 0;

        KnowledgeCategory::create($data);

        return redirect()
            ->route("knowledge.categories.index")
            ->with("success", "Категория успешно создана");
    }

    /**
     * Display the specified resource.
     */
    public function show(KnowledgeCategory $category)
    {
        $articles = $category
            ->knowledgeBase()
            ->with("author")
            ->latest()
            ->paginate(10);

        return view(
            "knowledge.categories.show",
            compact("category", "articles"),
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(KnowledgeCategory $category)
    {
        return view("knowledge.categories.edit", compact("category"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KnowledgeCategory $category)
    {
        $data = $request->validate([
            "name" => "required|string|max:255",
            "description" => "nullable|string|max:500",
            "icon" => "nullable|string|max:100",
            "color" =>
                'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            "sort_order" => "nullable|integer|min:0",
            "is_active" => "boolean",
        ]);

        $data["slug"] = Str::slug($data["name"]);
        $data["is_active"] = $request->has("is_active");
        $data["sort_order"] = $data["sort_order"] ?? $category->sort_order;

        $category->update($data);

        return redirect()
            ->route("knowledge.categories.index")
            ->with("success", "Категория успешно обновлена");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KnowledgeCategory $category)
    {
        // Проверяем, есть ли статьи в этой категории
        if ($category->knowledgeBase()->count() > 0) {
            return redirect()
                ->route("knowledge.categories.index")
                ->with(
                    "error",
                    "Нельзя удалить категорию, в которой есть статьи",
                );
        }

        $category->delete();

        return redirect()
            ->route("knowledge.categories.index")
            ->with("success", "Категория успешно удалена");
    }
}
