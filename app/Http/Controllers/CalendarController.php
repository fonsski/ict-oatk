<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCalendarEventRequest;
use App\Http\Requests\UpdateCalendarEventRequest;
use App\Models\CalendarEvent;
use App\Models\CalendarEventParticipant;
use App\Models\Room;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\Calendar\OccurrenceExpander;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CalendarController extends Controller
{
    public function __construct(
        private OccurrenceExpander $expander,
        private NotificationService $notifications,
    ) {
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
            "staff" => $this->staffForPicker(),
        ]);
    }

    public function store(StoreCalendarEventRequest $request)
    {
        $data = $request->validated();
        $participantIds = $data["participant_ids"] ?? [];
        unset($data["participant_ids"]);

        $data["organizer_id"] = Auth::id();
        $data["color"] = $data["color"] ?? "blue";
        $data["status"] = CalendarEvent::STATUS_CONFIRMED;

        [$event, $invitedIds] = DB::transaction(function () use ($data, $participantIds) {
            $event = CalendarEvent::create($data);
            $invited = $this->syncParticipants($event, $participantIds);
            return [$event, $invited];
        });

        // Рассылку приглашений держим вне транзакции: уведомления идут через
        // очередь и не должны откатывать создание события, если очередь легла.
        $this->inviteMany($event, $invitedIds);

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
            "staff" => $this->staffForPicker(),
            "selectedParticipantIds" => $event->participants()->pluck("user_id")->all(),
        ]);
    }

    public function update(UpdateCalendarEventRequest $request, CalendarEvent $event)
    {
        $this->authorizeManage($event);

        $data = $request->validated();
        $participantIds = $data["participant_ids"] ?? [];
        unset($data["participant_ids"]);

        // Меняются ли время/место — от этого зависит, беспокоить ли участников.
        $wasStart = $event->starts_at;
        $wasEnd = $event->ends_at;

        $invitedIds = DB::transaction(function () use ($event, $data, $participantIds) {
            $event->update($data);
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
