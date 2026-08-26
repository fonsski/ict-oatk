<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCalendarTaskRequest;
use App\Http\Requests\UpdateCalendarTaskRequest;
use App\Models\CalendarTask;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Личные задачи в календаре. Задача принадлежит одному сотруднику —
 * чужие не видны и не редактируются.
 */
class CalendarTaskController extends Controller
{
    public function store(StoreCalendarTaskRequest $request)
    {
        $data = $request->validated();
        unset($data["due_date"]);

        // Автор — текущий пользователь; исполнитель по умолчанию тоже он,
        // но задачу можно поручить другому сотруднику.
        $data["created_by_user_id"] = Auth::id();
        $data["user_id"] = $data["user_id"] ?? Auth::id();
        $data["priority"] = $data["priority"] ?? CalendarTask::PRIORITY_MEDIUM;

        $task = CalendarTask::create($data);

        return redirect()
            ->route("calendar.index", ["month" => CarbonImmutable::parse($task->due_at)->format("Y-m")])
            ->with("success", "Задача добавлена");
    }

    /**
     * Отметить задачу выполненной или снять отметку.
     */
    public function toggle(Request $request, CalendarTask $task)
    {
        $this->authorizeOwner($task);

        $task->update([
            "completed_at" => $task->completed_at ? null : now(),
        ]);

        return back()->with("success", $task->completed_at ? "Задача выполнена" : "Задача снова активна");
    }

    public function edit(CalendarTask $task)
    {
        $this->authorizeOwner($task);

        $task->load(["creator:id,name", "assignee:id,name"]);

        return view("calendar.task-edit", [
            "task" => $task,
            "staff" => \App\Models\User::query()
                ->where("is_active", true)
                ->whereHas("role", fn ($q) => $q->whereIn("slug", ["admin", "master", "technician"]))
                ->orderBy("name")
                ->get(["id", "name"]),
        ]);
    }

    public function update(UpdateCalendarTaskRequest $request, CalendarTask $task)
    {
        $this->authorizeOwner($task);

        $data = $request->validated();
        unset($data["due_date"]);

        $task->update($data);

        return redirect()
            ->route("calendar.index", ["month" => CarbonImmutable::parse($task->due_at)->format("Y-m")])
            ->with("success", "Задача обновлена");
    }

    public function destroy(CalendarTask $task)
    {
        $this->authorizeOwner($task);

        $month = CarbonImmutable::parse($task->due_at ?? now())->format("Y-m");
        $task->delete();

        return redirect()
            ->route("calendar.index", ["month" => $month])
            ->with("success", "Задача удалена");
    }

    private function authorizeOwner(CalendarTask $task): void
    {
        // Доступ у исполнителя, автора и управляющих.
        if (Auth::user()->hasRole(["admin", "master"])) {
            return;
        }
        if ($task->user_id !== Auth::id() && $task->created_by_user_id !== Auth::id()) {
            abort(403);
        }
    }
}
