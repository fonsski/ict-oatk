<?php

namespace App\Console\Commands;

use App\Models\CalendarEvent;
use App\Models\CalendarReminderDispatch;
use App\Services\NotificationService;
use App\Support\Calendar\OccurrenceExpander;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Рассылает напоминания о скоро начинающихся событиях.
 *
 * Запускается планировщиком раз в минуту. Для каждого события с заданным
 * напоминанием разворачивает ближайшие экземпляры и, если до начала осталось
 * не больше reminder_minutes и напоминание ещё не слали, уведомляет
 * организатора и согласившихся участников.
 */
class SendCalendarReminders extends Command
{
    protected $signature = "calendar:send-reminders";

    protected $description = "Разослать напоминания о скором начале событий календаря";

    public function handle(OccurrenceExpander $expander, NotificationService $notifications): int
    {
        $now = CarbonImmutable::now();

        $events = CalendarEvent::query()
            ->whereNotNull("reminder_minutes")
            ->where("status", CalendarEvent::STATUS_CONFIRMED)
            ->with(["exceptions", "organizer", "participants.user"])
            ->get();

        $sent = 0;

        foreach ($events as $event) {
            $windowEnd = $now->addMinutes($event->reminder_minutes);

            // Экземпляры, начинающиеся в ближайшее окно напоминания.
            $occurrences = $expander->expand(collect([$event]), $now, $windowEnd);

            foreach ($occurrences as $occ) {
                // Уже начались или ещё слишком далеко — пропускаем.
                if ($occ->startsAt->lt($now) || $occ->startsAt->gt($windowEnd)) {
                    continue;
                }

                $already = CalendarReminderDispatch::query()
                    ->where("event_id", $event->id)
                    ->where("occurrence_starts_at", $occ->startsAt->toDateTimeString())
                    ->exists();

                if ($already) {
                    continue;
                }

                foreach ($this->recipients($event) as $user) {
                    $notifications->notifyEventReminder($event, $user, $occ->startsAt);
                }

                CalendarReminderDispatch::create([
                    "event_id" => $event->id,
                    "occurrence_starts_at" => $occ->startsAt->toDateTimeString(),
                    "sent_at" => $now->toDateTimeString(),
                ]);

                $sent++;
            }
        }

        $this->info("Отправлено напоминаний: {$sent}");

        return self::SUCCESS;
    }

    /**
     * Кому напоминать: организатор и участники, не отказавшиеся прийти.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\User>
     */
    private function recipients(CalendarEvent $event): \Illuminate\Support\Collection
    {
        $users = collect();

        if ($event->organizer) {
            $users->push($event->organizer);
        }

        foreach ($event->participants as $participant) {
            if ($participant->response !== "declined" && $participant->user) {
                $users->push($participant->user);
            }
        }

        return $users->unique("id")->values();
    }
}
