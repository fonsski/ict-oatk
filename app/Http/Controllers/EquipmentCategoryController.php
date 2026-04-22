<?php

namespace App\Http\Controllers;

use App\Models\EquipmentCategory;
use App\Http\Requests\StoreEquipmentCategoryRequest;
use App\Http\Requests\UpdateEquipmentCategoryRequest;
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
    public function index(Request $request): \Illuminate\Contracts\View\View
    {
        // Проверка прав доступа
        if (!Auth::user()->canManageEquipment()) {
            abort(403, "У вас нет прав на управление категориями оборудования");
        }

        $query = EquipmentCategory::query();

        // Поиск по имени или описанию
        if ($request->has("search") && !empty($request->get("search"))) {
            $query->where(function ($q) use ($request) {
                $search = $request->get("search");
                $q->where("name", "like", "%{$search}%")->orWhere(
                    "description",
                    "like",
                    "%{$search}%",
                );
            });
        }

        // Сортировка
        $sortField = $request->get("sort", "name");
        $sortDirection = $request->get("direction", "asc");

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
    public function create(): \Illuminate\Contracts\View\View
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
    public function store(StoreEquipmentCategoryRequest $request): \Illuminate\Http\RedirectResponse
    {
        // Проверка прав доступа
        if (!Auth::user()->canManageEquipment()) {
            abort(403, "У вас нет прав на управление категориями оборудования");
        }

        $validated = $request->validated();

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
     * @param  \App\Models\EquipmentCategory  $equipmentCategory
     * @return \Illuminate\Http\Response
     */
    public function show(
        EquipmentCategory $equipmentCategory,
    ): \Illuminate\Contracts\View\View {
        $equipment = $equipmentCategory
            ->equipment()
            ->with(["status", "room"])
            ->paginate(15);

        return view(
            "equipment.categories.show",
            compact("equipmentCategory", "equipment"),
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EquipmentCategory  $equipmentCategory
     * @return \Illuminate\Http\Response
     */
    public function edit(
        EquipmentCategory $equipmentCategory,
    ): \Illuminate\Contracts\View\View {
        // Проверка прав доступа
        if (!Auth::user()->canManageEquipment()) {
            abort(403, "У вас нет прав на управление категориями оборудования");
        }

        return view("equipment.categories.edit", compact("equipmentCategory"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EquipmentCategory  $equipmentCategory
     * @return \Illuminate\Http\Response
     */
    public function update(
        UpdateEquipmentCategoryRequest $request,
        EquipmentCategory $equipmentCategory,
    ): \Illuminate\Http\RedirectResponse {
        // Проверка прав доступа
        if (!Auth::user()->canManageEquipment()) {
            abort(403, "У вас нет прав на управление категориями оборудования");
        }

        $validated = $request->validated();

        // Обновляем slug только если изменилось имя
        if ($validated["name"] !== $equipmentCategory->name) {
            $validated["slug"] = Str::slug($validated["name"]);
        }

        $equipmentCategory->update($validated);

        return redirect()
            ->route("equipment.equipment-categories.index")
            ->with("success", "Категория успешно обновлена");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EquipmentCategory  $equipmentCategory
     * @return \Illuminate\Http\Response
     */
    public function destroy(
        EquipmentCategory $equipmentCategory,
    ): \Illuminate\Http\RedirectResponse {
        // Проверка прав доступа
        if (!Auth::user()->canManageEquipment()) {
            abort(403, "У вас нет прав на управление категориями оборудования");
        }

        // Проверяем, есть ли оборудование в этой категории
        if ($equipmentCategory->equipment()->count() > 0) {
            return redirect()
                ->route("equipment.equipment-categories.index")
                ->with(
                    "error",
                    "Невозможно удалить категорию, к которой привязано оборудование",
                );
        }

        $equipmentCategory->delete();

        return redirect()
            ->route("equipment.equipment-categories.index")
            ->with("success", "Категория успешно удалена");
    }
}
