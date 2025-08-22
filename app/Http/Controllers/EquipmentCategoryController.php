<?php

namespace App\Http\Controllers;

use App\Models\EquipmentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EquipmentCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Проверка прав доступа
        if (!Auth::user()->canManageEquipment()) {
            abort(403, "У вас нет прав на управление категориями оборудования");
        }

        $query = EquipmentCategory::query();

        // Поиск по имени или описанию
        if ($request->filled("search")) {
            $query->search($request->input("search"));
        }

        // Сортировка
        $sortField = $request->input("sort", "name");
        $sortDirection = $request->input("direction", "asc");

        if (in_array($sortField, ["name", "created_at", "updated_at"])) {
            $query->orderBy($sortField, $sortDirection);
        }

        $categories = $query
            ->withCount("equipment")
            ->paginate(15)
            ->withQueryString();

        return view("equipment.categories.index", compact("categories"));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Проверка прав доступа
        if (!Auth::user()->canManageEquipment()) {
            abort(403, "У вас нет прав на управление категориями оборудования");
        }

        return view("equipment.categories.create");
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Проверка прав доступа
        if (!Auth::user()->canManageEquipment()) {
            abort(403, "У вас нет прав на управление категориями оборудования");
        }

        $validated = $request->validate([
            "name" => "required|string|max:255|unique:equipment_categories",
            "description" => "nullable|string",
        ]);

        // Автоматически генерируем slug из имени
        $validated["slug"] = Str::slug($validated["name"]);

        $category = EquipmentCategory::create($validated);

        return redirect()
            ->route("equipment.equipment-categories.index")
            ->with("success", "Категория успешно создана");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\EquipmentCategory  $category
     * @return \Illuminate\Http\Response
     */
    public function show(EquipmentCategory $category)
    {
        $equipment = $category
            ->equipment()
            ->with(["status", "room"])
            ->paginate(15);

        return view(
            "equipment.categories.show",
            compact("category", "equipment"),
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EquipmentCategory  $category
     * @return \Illuminate\Http\Response
     */
    public function edit(EquipmentCategory $category)
    {
        // Проверка прав доступа
        if (!Auth::user()->canManageEquipment()) {
            abort(403, "У вас нет прав на управление категориями оборудования");
        }

        return view("equipment.categories.edit", compact("category"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EquipmentCategory  $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EquipmentCategory $category)
    {
        // Проверка прав доступа
        if (!Auth::user()->canManageEquipment()) {
            abort(403, "У вас нет прав на управление категориями оборудования");
        }

        $validated = $request->validate([
            "name" => [
                "required",
                "string",
                "max:255",
                Rule::unique("equipment_categories")->ignore($category->id),
            ],
            "description" => "nullable|string",
        ]);

        // Обновляем slug только если изменилось имя
        if ($validated["name"] !== $category->name) {
            $validated["slug"] = Str::slug($validated["name"]);
        }

        $category->update($validated);

        return redirect()
            ->route("equipment.equipment-categories.index")
            ->with("success", "Категория успешно обновлена");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EquipmentCategory  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy(EquipmentCategory $category)
    {
        // Проверка прав доступа
        if (!Auth::user()->canManageEquipment()) {
            abort(403, "У вас нет прав на управление категориями оборудования");
        }

        // Проверяем, есть ли оборудование в этой категории
        if ($category->equipment()->count() > 0) {
            return redirect()
                ->route("equipment.equipment-categories.index")
                ->with(
                    "error",
                    "Невозможно удалить категорию, к которой привязано оборудование",
                );
        }

        $category->delete();

        return redirect()
            ->route("equipment.equipment-categories.index")
            ->with("success", "Категория успешно удалена");
    }
}
