<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOperatingSystemRequest;
use App\Models\OperatingSystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OperatingSystemController extends Controller
{
    public function index(Request $request)
    {
        $query = OperatingSystem::withCount("equipment");

        if ($request->filled("search")) {
            $search = $request->input("search");
            $query->where(function ($q) use ($search) {
                $q->where("name", "like", "%{$search}%")
                    ->orWhere("family", "like", "%{$search}%");
            });
        }

        if ($request->filled("family")) {
            $query->where("family", $request->input("family"));
        }

        $operatingSystems = $query->ordered()->paginate(20)->withQueryString();
        $families = OperatingSystem::whereNotNull("family")
            ->distinct()
            ->orderBy("family")
            ->pluck("family");

        return view(
            "operating-systems.index",
            compact("operatingSystems", "families"),
        );
    }

    public function create()
    {
        $this->authorizeManage();

        return view("operating-systems.create");
    }

    public function store(StoreOperatingSystemRequest $request)
    {
        $this->authorizeManage();

        OperatingSystem::create($this->prepare($request->validated()));

        return redirect()
            ->route("operating-systems.index")
            ->with("success", "Операционная система добавлена");
    }

    public function edit(OperatingSystem $operatingSystem)
    {
        $this->authorizeManage();

        return view("operating-systems.edit", compact("operatingSystem"));
    }

    public function update(StoreOperatingSystemRequest $request, OperatingSystem $operatingSystem)
    {
        $this->authorizeManage();

        $operatingSystem->update($this->prepare($request->validated(), $operatingSystem));

        return redirect()
            ->route("operating-systems.index")
            ->with("success", "Операционная система обновлена");
    }

    public function destroy(OperatingSystem $operatingSystem)
    {
        $this->authorizeManage();

        // Оборудование не удаляем — у него просто снимется привязка (nullOnDelete).
        $usedBy = $operatingSystem->equipment()->count();
        $operatingSystem->delete();

        $message = "Операционная система удалена";
        if ($usedBy > 0) {
            $message .= ". У {$usedBy} ед. оборудования ОС теперь не указана";
        }

        return redirect()
            ->route("operating-systems.index")
            ->with("success", $message);
    }

    /**
     * Slug генерируем сами: названия ОС часто кириллические («РЕД ОС»),
     * поэтому подстраховываемся транслитерацией и уникальным суффиксом.
     */
    private function prepare(array $data, ?OperatingSystem $existing = null): array
    {
        $data["is_active"] = (bool) ($data["is_active"] ?? false);
        $data["sort_order"] = $data["sort_order"] ?? 0;

        if (!$existing || $existing->name !== $data["name"]) {
            $base = Str::slug($data["name"]) ?: Str::slug(Str::ascii($data["name"]));
            $base = $base ?: "os";
            $slug = $base;
            $suffix = 2;

            while (
                OperatingSystem::where("slug", $slug)
                    ->when($existing, fn($q) => $q->whereKeyNot($existing->id))
                    ->exists()
            ) {
                $slug = "{$base}-{$suffix}";
                $suffix++;
            }

            $data["slug"] = $slug;
        }

        return $data;
    }

    private function authorizeManage(): void
    {
        if (
            !Auth::check() ||
            !in_array(optional(Auth::user()->role)->slug, ["admin", "master"])
        ) {
            abort(403, "Только администраторы и мастера могут менять справочник ОС");
        }
    }
}
