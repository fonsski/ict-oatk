<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCalendarEventRequest;
use App\Http\Requests\UpdateCalendarEventRequest;
use App\Models\CalendarEvent;
use App\Models\Room;
use App\Support\Calendar\OccurrenceExpander;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    public function __construct(private OccurrenceExpander $expander)
    {
    }

    /**
     * Вид «Месяц»: сетка недель с экземплярами событий по дням.
     */
    public function index(Request $request)
    {
        $month = $this->resolveMonth($request->query("month"));

        // Сетка месяца выходит за его края: показываем «хвосты» соседних
        // месяцев, чтобы недели были полными (как в Google Calendar).
        $gridStart = $month->startOfMonth()->startOfWeek(CarbonImmutable::MONDAY);
        $gridEnd = $month->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY);

        $events = CalendarEvent::query()
            ->where("status", CalendarEvent::STATUS_CONFIRMED)
            ->overlapping($gridStart, $gridEnd)
            ->with(["organizer:id,name", "room:id,number,name", "exceptions"])
            ->visibleTo(Auth::user())
            ->get();

        $occurrences = $this->expander
            ->expand($events, $gridStart, $gridEnd)
            ->groupBy(fn ($o) => $o->startsAt->toDateString());

        $weeks = $this->buildWeeks($gridStart, $gridEnd, $month, $occurrences);

        return view("calendar.index", [
            "month" => $month,
            "weeks" => $weeks,
            "prevMonth" => $month->subMonth()->format("Y-m"),
            "nextMonth" => $month->addMonth()->format("Y-m"),
            "today" => CarbonImmutable::today(),
            "rooms" => Room::orderBy("number")->get(["id", "number", "name"]),
        ]);
    }

    public function store(StoreCalendarEventRequest $request)
    {
        $data = $request->validated();
        $data["organizer_id"] = Auth::id();
        $data["color"] = $data["color"] ?? "blue";
        $data["status"] = CalendarEvent::STATUS_CONFIRMED;

        $event = CalendarEvent::create($data);

        return redirect()
            ->route("calendar.index", ["month" => CarbonImmutable::parse($event->starts_at)->format("Y-m")])
            ->with("success", "Событие создано");
    }

    public function show(CalendarEvent $event)
    {
        $this->authorizeView($event);

        $event->load(["organizer:id,name", "room:id,number,name", "participants.user:id,name"]);

        return view("calendar.show", ["event" => $event]);
    }

    public function edit(CalendarEvent $event)
    {
        $this->authorizeManage($event);

        return view("calendar.edit", [
            "event" => $event,
            "rooms" => Room::orderBy("number")->get(["id", "number", "name"]),
        ]);
    }

    public function update(UpdateCalendarEventRequest $request, CalendarEvent $event)
    {
        $this->authorizeManage($event);

        $event->update($request->validated());

        return redirect()
            ->route("calendar.show", $event)
            ->with("success", "Событие обновлено");
    }

    public function destroy(CalendarEvent $event)
    {
        $this->authorizeManage($event);

        $month = CarbonImmutable::parse($event->starts_at)->format("Y-m");
        $event->delete();

        return redirect()
            ->route("calendar.index", ["month" => $month])
            ->with("success", "Событие удалено");
    }

    private function resolveMonth(?string $value): CarbonImmutable
    {
        if ($value && preg_match('/^\d{4}-\d{2}$/', $value)) {
            try {
                return CarbonImmutable::createFromFormat("Y-m-d", $value . "-01")->startOfMonth();
            } catch (\Throwable) {
                // Кривой параметр — просто показываем текущий месяц.
            }
        }

        return CarbonImmutable::today()->startOfMonth();
    }

    /**
     * Раскладывает диапазон дат по неделям для сетки.
     *
     * @return array<int, array<int, array>>
     */
    private function buildWeeks(CarbonImmutable $gridStart, CarbonImmutable $gridEnd, CarbonImmutable $month, $occurrences): array
    {
        $weeks = [];
        $week = [];
        $day = $gridStart;

        while ($day->lte($gridEnd)) {
            $week[] = [
                "date" => $day,
                "inMonth" => $day->month === $month->month,
                "occurrences" => $occurrences->get($day->toDateString(), collect()),
            ];

            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }

            $day = $day->addDay();
        }

        return $weeks;
    }

    private function authorizeView(CalendarEvent $event): void
    {
        $user = Auth::user();
        // Управляющие видят всё; остальные — своё (организатор или участник).
        if ($user->hasRole(["admin", "master"])) {
            return;
        }
        if ($event->organizer_id === $user->id) {
            return;
        }
        if ($event->participants()->where("user_id", $user->id)->exists()) {
            return;
        }

        abort(403);
    }

    private function authorizeManage(CalendarEvent $event): void
    {
        $user = Auth::user();
        // Править и удалять может организатор или управляющий.
        if ($user->hasRole(["admin", "master"]) || $event->organizer_id === $user->id) {
            return;
        }

        abort(403);
    }
}
