<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCalendarEventRequest;
use App\Http\Requests\UpdateCalendarEventRequest;
use App\Models\CalendarEvent;
use App\Models\CalendarEventParticipant;
use App\Models\CalendarTask;
use App\Models\Room;
use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\Calendar\OccurrenceExpander;
use App\Support\Calendar\RoomAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CalendarController extends Controller
{
    public function __construct(
        private OccurrenceExpander $expander,
        private NotificationService $notifications,
        private RoomAvailability $roomAvailability,
    ) {
    }

    /**
     * Заявки для привязки к событию. Управляющие видят все, техник — свои
     * и назначенные на него.
     */
    private function ticketsForPicker(): \Illuminate\Support\Collection
    {
        $user = Auth::user();

        return Ticket::query()
            ->when(
                !$user->hasRole(["admin", "master"]),
                fn ($q) => $q->where(function ($w) use ($user) {
                    $w->where("user_id", $user->id)->orWhere("assigned_to_id", $user->id);
                }),
            )
            ->with("room:id,number")
            ->latest()
            ->limit(50)
            ->get(["id", "title", "status", "room_id"]);
    }

    /**
     * Сотрудники, которых можно позвать участниками (кроме самого себя).
     */
    private function staffForPicker(): \Illuminate\Support\Collection
    {
        return User::query()
            ->where("is_active", true)
            ->whereHas("role", fn ($q) => $q->whereIn("slug", ["admin", "master", "technician"]))
            ->where("id", "!=", Auth::id())
            ->orderBy("name")
            ->get(["id", "name", "position"]);
    }

    /**
     * Календарь. Вид выбирается параметром view: month | week | day.
     */
    public function index(Request $request)
    {
        return match ($request->query("view")) {
            "week" => $this->weekView($request),
            "day" => $this->dayView($request),
            default => $this->monthView($request),
        };
    }

    /**
     * Вид «Месяц»: сетка недель с экземплярами событий по дням.
     */
    private function monthView(Request $request)
    {
        $month = $this->resolveMonth($request->query("month") ?? $request->query("date"));

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

        // Личные задачи текущего пользователя с датой в пределах сетки.
        $tasks = CalendarTask::query()
            ->where("user_id", Auth::id())
            ->whereNotNull("due_at")
            ->whereBetween("due_at", [$gridStart, $gridEnd])
            ->orderBy("due_at")
            ->get()
            ->groupBy(fn ($t) => CarbonImmutable::parse($t->due_at)->toDateString());

        $weeks = $this->buildWeeks($gridStart, $gridEnd, $month, $occurrences, $tasks);

        return view("calendar.index", [
            "month" => $month,
            "weeks" => $weeks,
            "prevMonth" => $month->subMonth()->format("Y-m"),
            "nextMonth" => $month->addMonth()->format("Y-m"),
            "today" => CarbonImmutable::today(),
            "rooms" => Room::orderBy("number")->get(["id", "number", "name"]),
            "staff" => $this->staffForPicker(),
            "tickets" => $this->ticketsForPicker(),
        ]);
    }

    /**
     * Вид «Неделя»: 7 дней с часовой сеткой.
     */
    private function weekView(Request $request)
    {
        $anchor = $this->resolveDate($request->query("date"));
        $start = $anchor->startOfWeek(CarbonImmutable::MONDAY);
        $end = $anchor->endOfWeek(CarbonImmutable::SUNDAY);

        return view("calendar.week", array_merge(
            $this->timeGridData($start, $end),
            [
                "rangeStart" => $start,
                "rangeEnd" => $end,
                "prevDate" => $anchor->subWeek()->toDateString(),
                "nextDate" => $anchor->addWeek()->toDateString(),
                "todayDate" => CarbonImmutable::today()->toDateString(),
                "rooms" => Room::orderBy("number")->get(["id", "number", "name"]),
                "staff" => $this->staffForPicker(),
                "tickets" => $this->ticketsForPicker(),
            ],
        ));
    }

    /**
     * Вид «День»: один день с часовой сеткой.
     */
    private function dayView(Request $request)
    {
        $day = $this->resolveDate($request->query("date"));
        $start = $day->startOfDay();
        $end = $day->endOfDay();

        return view("calendar.day", array_merge(
            $this->timeGridData($start, $end),
            [
                "rangeStart" => $start,
                "rangeEnd" => $end,
                "prevDate" => $day->subDay()->toDateString(),
                "nextDate" => $day->addDay()->toDateString(),
                "todayDate" => CarbonImmutable::today()->toDateString(),
                "rooms" => Room::orderBy("number")->get(["id", "number", "name"]),
                "staff" => $this->staffForPicker(),
                "tickets" => $this->ticketsForPicker(),
            ],
        ));
    }

    /**
     * Данные для видов с часовой сеткой (неделя и день).
     *
     * По каждому дню отрезка: события с временем — с раскладкой пересечений,
     * события «весь день» и задачи — отдельными строками сверху.
     */
    private function timeGridData(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $events = CalendarEvent::query()
            ->where("status", CalendarEvent::STATUS_CONFIRMED)
            ->overlapping($start, $end)
            ->with(["organizer:id,name", "room:id,number,name", "exceptions"])
            ->visibleTo(Auth::user())
            ->get();

        $occurrences = $this->expander
            ->expand($events, $start, $end)
            ->groupBy(fn ($o) => $o->startsAt->toDateString());

        $tasks = CalendarTask::query()
            ->where("user_id", Auth::id())
            ->whereNotNull("due_at")
            ->whereBetween("due_at", [$start, $end])
            ->orderBy("due_at")
            ->get()
            ->groupBy(fn ($t) => CarbonImmutable::parse($t->due_at)->toDateString());

        $days = [];
        $day = $start->startOfDay();
        $lastDay = $end->startOfDay();

        while ($day->lte($lastDay)) {
            $key = $day->toDateString();
            $dayOccurrences = $occurrences->get($key, collect());

            $days[] = [
                "date" => $day,
                "allDay" => $dayOccurrences->filter(fn ($o) => $o->isAllDay())->values(),
                "timed" => $this->layoutTimed(
                    $dayOccurrences->reject(fn ($o) => $o->isAllDay())->values(),
                ),
                "tasks" => $tasks->get($key, collect()),
            ];

            $day = $day->addDay();
        }

        return ["days" => $days];
    }

    /**
     * Раскладка событий с временем внутри дня: пересекающиеся ставятся в
     * соседние колонки, чтобы не наезжать друг на друга (как в Google).
     *
     * @return array<int, array{occ: \App\Support\Calendar\EventOccurrence, startMin: int, endMin: int, col: int, cols: int}>
     */
    private function layoutTimed(\Illuminate\Support\Collection $occurrences): array
    {
        $items = $occurrences
            ->map(function ($occ) {
                $startMin = (int) $occ->startsAt->format("H") * 60 + (int) $occ->startsAt->format("i");
                // Окончание может уходить в следующий день — прижимаем к концу суток.
                $endMin = $occ->endsAt->toDateString() === $occ->startsAt->toDateString()
                    ? (int) $occ->endsAt->format("H") * 60 + (int) $occ->endsAt->format("i")
                    : 24 * 60;
                // Минимальная высота, чтобы совсем короткие были кликабельны.
                $endMin = max($endMin, $startMin + 20);

                return ["occ" => $occ, "startMin" => $startMin, "endMin" => $endMin, "col" => 0, "cols" => 1];
            })
            ->sortBy("startMin")
            ->values()
            ->all();

        // Жадная раскладка по кластерам пересечения.
        $cluster = [];
        $clusterEnd = -1;

        $flush = function (array &$cluster): void {
            $columns = []; // время окончания последнего события в каждой колонке
            foreach ($cluster as &$item) {
                $placed = false;
                foreach ($columns as $ci => $colEnd) {
                    if ($item["startMin"] >= $colEnd) {
                        $item["col"] = $ci;
                        $columns[$ci] = $item["endMin"];
                        $placed = true;
                        break;
                    }
                }
                if (!$placed) {
                    $item["col"] = count($columns);
                    $columns[] = $item["endMin"];
                }
            }
            unset($item);

            $total = max(1, count($columns));
            foreach ($cluster as &$item) {
                $item["cols"] = $total;
            }
            unset($item);
        };

        $result = [];
        foreach ($items as $item) {
            if (!empty($cluster) && $item["startMin"] >= $clusterEnd) {
                $flush($cluster);
                $result = array_merge($result, $cluster);
                $cluster = [];
                $clusterEnd = -1;
            }

            $cluster[] = $item;
            $clusterEnd = max($clusterEnd, $item["endMin"]);
        }
        if (!empty($cluster)) {
            $flush($cluster);
            $result = array_merge($result, $cluster);
        }

        return $result;
    }

    private function resolveDate(?string $value): CarbonImmutable
    {
        if ($value) {
            try {
                return CarbonImmutable::parse($value);
            } catch (\Throwable) {
                // Кривой параметр — показываем сегодня.
            }
        }

        return CarbonImmutable::today();
    }

    public function store(StoreCalendarEventRequest $request)
    {
        $data = $request->validated();
        $participantIds = $data["participant_ids"] ?? [];
        $ticketId = $data["ticket_id"] ?? null;
        $equipmentIds = $data["equipment_ids"] ?? [];
        $ignoreConflict = (bool) ($data["ignore_room_conflict"] ?? false);
        unset($data["participant_ids"], $data["ticket_id"], $data["equipment_ids"], $data["ignore_room_conflict"]);

        // Кабинет физически один — предупреждаем о накладке, если не просят
        // бронировать несмотря на занятость.
        if ($conflict = $this->roomConflictError($data, null, $ignoreConflict)) {
            return $conflict;
        }

        $data["organizer_id"] = Auth::id();
        $data["color"] = $data["color"] ?? "blue";
        $data["status"] = CalendarEvent::STATUS_CONFIRMED;

        [$event, $invitedIds] = DB::transaction(function () use ($data, $participantIds, $ticketId, $equipmentIds) {
            $event = CalendarEvent::create($data);
            $invited = $this->syncParticipants($event, $participantIds);
            $this->syncLinks($event, $ticketId, $equipmentIds);
            return [$event, $invited];
        });

        // Рассылку приглашений держим вне транзакции: уведомления идут через
        // очередь и не должны откатывать создание события, если очередь легла.
        $this->inviteMany($event, $invitedIds);

        return redirect()
            ->route("calendar.index", ["month" => CarbonImmutable::parse($event->starts_at)->format("Y-m")])
            ->with("success", "Событие создано");
    }

    public function show(Request $request, CalendarEvent $event)
    {
        $this->authorizeView($event);

        $event->load([
            "organizer:id,name",
            "room:id,number,name",
            "participants.user:id,name",
            "tickets:id,title,status",
            "equipment:id,name,inventory_number",
        ]);

        // Если пришли с конкретного экземпляра серии — знаем его дату и
        // можем предложить отменить только её.
        $occurrenceDate = null;
        if ($event->isRecurring() && $request->query("date")) {
            try {
                $occurrenceDate = CarbonImmutable::parse($request->query("date"));
            } catch (\Throwable) {
                // Кривая дата — покажем событие как серию целиком.
            }
        }

        return view("calendar.show", [
            "event" => $event,
            "occurrenceDate" => $occurrenceDate,
        ]);
    }

    /**
     * Отменить один экземпляр повторяющегося события, не трогая серию.
     */
    public function cancelOccurrence(Request $request, CalendarEvent $event)
    {
        $this->authorizeManage($event);

        $validated = $request->validate(["date" => "required|date"]);
        $date = CarbonImmutable::parse($validated["date"])->toDateString();

        $event->exceptions()->updateOrCreate(
            ["occurrence_date" => $date],
            ["is_cancelled" => true],
        );

        return redirect()
            ->route("calendar.index", ["month" => CarbonImmutable::parse($date)->format("Y-m")])
            ->with("success", "Эта дата события отменена");
    }

    public function edit(CalendarEvent $event)
    {
        $this->authorizeManage($event);

        return view("calendar.edit", [
            "event" => $event,
            "rooms" => Room::orderBy("number")->get(["id", "number", "name"]),
            "staff" => $this->staffForPicker(),
            "tickets" => $this->ticketsForPicker(),
            "selectedParticipantIds" => $event->participants()->pluck("user_id")->all(),
            "selectedTicketId" => $event->tickets()->value("tickets.id"),
        ]);
    }

    public function update(UpdateCalendarEventRequest $request, CalendarEvent $event)
    {
        $this->authorizeManage($event);

        $data = $request->validated();
        $participantIds = $data["participant_ids"] ?? [];
        $ticketId = $data["ticket_id"] ?? null;
        $equipmentIds = $data["equipment_ids"] ?? [];
        $ignoreConflict = (bool) ($data["ignore_room_conflict"] ?? false);
        unset($data["participant_ids"], $data["ticket_id"], $data["equipment_ids"], $data["ignore_room_conflict"]);

        // При правке из проверки исключаем само событие.
        if ($conflict = $this->roomConflictError($data, $event->id, $ignoreConflict)) {
            return $conflict;
        }

        // Меняются ли время/место — от этого зависит, беспокоить ли участников.
        $wasStart = $event->starts_at;
        $wasEnd = $event->ends_at;

        $invitedIds = DB::transaction(function () use ($event, $data, $participantIds, $ticketId, $equipmentIds) {
            $event->update($data);
            $this->syncLinks($event, $ticketId, $equipmentIds);
            return $this->syncParticipants($event, $participantIds);
        });

        $this->inviteMany($event, $invitedIds);

        // Уже приглашённым (не новичкам) сообщаем, если сдвинулось время.
        $timeChanged = !$event->starts_at->equalTo($wasStart) || !$event->ends_at->equalTo($wasEnd);
        if ($timeChanged) {
            $event->load("participants.user");
            foreach ($event->participants as $participant) {
                if (!in_array($participant->user_id, $invitedIds, true)) {
                    $this->notifications->notifyEventChanged($event, $participant->user, "updated");
                }
            }
        }

        return redirect()
            ->route("calendar.show", $event)
            ->with("success", "Событие обновлено");
    }

    public function destroy(CalendarEvent $event)
    {
        $this->authorizeManage($event);

        $month = CarbonImmutable::parse($event->starts_at)->format("Y-m");

        // Предупреждаем участников до удаления, пока связи ещё на месте.
        $event->load("participants.user");
        foreach ($event->participants as $participant) {
            if ($participant->user_id !== Auth::id()) {
                $this->notifications->notifyEventChanged($event, $participant->user, "cancelled");
            }
        }

        $event->delete();

        return redirect()
            ->route("calendar.index", ["month" => $month])
            ->with("success", "Событие удалено");
    }

    /**
     * Ответ участника на приглашение (RSVP).
     */
    public function respond(Request $request, CalendarEvent $event)
    {
        $validated = $request->validate([
            "response" => "required|in:accepted,declined,maybe",
        ]);

        $participant = $event
            ->participants()
            ->where("user_id", Auth::id())
            ->first();

        if (!$participant) {
            abort(403, "Вы не в списке участников этого события.");
        }

        $participant->update([
            "response" => $validated["response"],
            "responded_at" => now(),
        ]);

        $this->notifications->notifyEventResponse(
            $event,
            Auth::user(),
            $participant->response_label,
        );

        return redirect()
            ->route("calendar.show", $event)
            ->with("success", "Ваш ответ сохранён");
    }

    /**
     * Приводит список участников события к переданному набору сотрудников.
     * Новых добавляет со статусом «ожидает», выбывших — удаляет.
     *
     * @param  array<int>  $ids
     * @return array<int>  user_id тех, кого пригласили только что
     */
    private function syncParticipants(CalendarEvent $event, array $ids): array
    {
        // Организатора в участники не пишем — он и так владелец события.
        $ids = collect($ids)
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === (int) $event->organizer_id)
            ->unique()
            ->values();

        $existing = $event->participants()->pluck("user_id");

        $toAdd = $ids->diff($existing);
        $toRemove = $existing->diff($ids);

        if ($toRemove->isNotEmpty()) {
            $event->participants()->whereIn("user_id", $toRemove)->delete();
        }

        foreach ($toAdd as $userId) {
            $event->participants()->create([
                "user_id" => $userId,
                "response" => CalendarEventParticipant::RESPONSE_PENDING,
            ]);
        }

        return $toAdd->all();
    }

    /**
     * Если кабинет занят и бронировать «несмотря на» не просили — возвращает
     * готовый redirect с ошибкой; иначе null.
     */
    private function roomConflictError(array $data, ?int $exceptId, bool $ignore)
    {
        if (empty($data["room_id"]) || $ignore) {
            return null;
        }

        $conflicts = $this->roomAvailability->conflicts(
            (int) $data["room_id"],
            CarbonImmutable::parse($data["starts_at"]),
            CarbonImmutable::parse($data["ends_at"]),
            $exceptId,
        );

        if ($conflicts->isEmpty()) {
            return null;
        }

        return back()
            ->withInput()
            ->withErrors([
                "room_id" =>
                    "Кабинет занят: " . $this->roomAvailability->describe($conflicts) .
                    ". Отметьте «бронировать несмотря на занятость», если это допустимо.",
            ]);
    }

    /**
     * Приводит связи события к переданным заявке и оборудованию.
     *
     * @param  array<int>  $equipmentIds
     */
    private function syncLinks(CalendarEvent $event, ?int $ticketId, array $equipmentIds): void
    {
        $event->tickets()->sync($ticketId ? [$ticketId] : []);
        $event->equipment()->sync($equipmentIds);
    }

    /**
     * @param  array<int>  $userIds
     */
    private function inviteMany(CalendarEvent $event, array $userIds): void
    {
        if (empty($userIds)) {
            return;
        }

        $users = User::whereIn("id", $userIds)->get();
        foreach ($users as $user) {
            $this->notifications->notifyEventInvitation($event, $user);
        }
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
    private function buildWeeks(CarbonImmutable $gridStart, CarbonImmutable $gridEnd, CarbonImmutable $month, $occurrences, $tasks): array
    {
        $weeks = [];
        $week = [];
        $day = $gridStart;

        while ($day->lte($gridEnd)) {
            $week[] = [
                "date" => $day,
                "inMonth" => $day->month === $month->month,
                "occurrences" => $occurrences->get($day->toDateString(), collect()),
                "tasks" => $tasks->get($day->toDateString(), collect()),
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
