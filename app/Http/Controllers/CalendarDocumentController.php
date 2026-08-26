<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\CalendarTask;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Документы, прикреплённые к событиям и задачам календаря.
 *
 * Отдельно от DocumentController: там загрузка разрешена только
 * администраторам и мастерам. Здесь документ к событию или задаче может
 * приложить любой, кто их видит, — в том числе техник. Каждый документ
 * помечается открытым или закрытым: закрытый виден только загрузившему и
 * управляющим.
 */
class CalendarDocumentController extends Controller
{
    /** Загрузить документ к событию или задаче. */
    public function store(Request $request, string $type, int $id)
    {
        $subject = $this->resolveSubject($type, $id);
        abort_unless($this->canView($subject), 403);

        $data = $request->validate([
            "file" => [
                "required",
                "file",
                "max:" . Document::MAX_SIZE_KB,
                "mimes:" . Document::ALLOWED_MIMES,
            ],
            "description" => "nullable|string|max:255",
            "is_private" => "boolean",
        ]);

        $file = $request->file("file");
        $path = $file->store("documents/calendar-{$type}/{$subject->id}", "local");

        $subject->documents()->create([
            "type" => Document::TYPE_OTHER,
            "path" => $path,
            "original_name" => $file->getClientOriginalName(),
            "mime_type" => $file->getClientMimeType(),
            "size" => $file->getSize(),
            "description" => $data["description"] ?? null,
            "is_private" => (bool) ($data["is_private"] ?? false),
            "uploaded_by_user_id" => Auth::id(),
        ]);

        return back()->with("success", "Документ прикреплён");
    }

    /** Скачать документ события или задачи (приватный диск). */
    public function download(Document $document)
    {
        abort_unless($this->canSeeDocument($document), 403);

        if (!Storage::disk("local")->exists($document->path)) {
            abort(404);
        }

        return Storage::disk("local")->download($document->path, $document->original_name);
    }

    /** Удалить документ. Может тот, кто загрузил, и управляющие. */
    public function destroy(Document $document)
    {
        $this->subjectOf($document); // проверяем, что документ вправду календарный

        abort_unless(
            $document->uploaded_by_user_id === Auth::id() || $this->isManager(),
            403,
        );

        Storage::disk("local")->delete($document->path);
        $document->delete();

        return back()->with("success", "Документ удалён");
    }

    private function resolveSubject(string $type, int $id): CalendarEvent|CalendarTask
    {
        return match ($type) {
            "event" => CalendarEvent::findOrFail($id),
            "task" => CalendarTask::findOrFail($id),
            default => abort(404),
        };
    }

    private function subjectOf(Document $document): CalendarEvent|CalendarTask
    {
        $subject = $document->documentable;

        if (!$subject instanceof CalendarEvent && !$subject instanceof CalendarTask) {
            abort(404);
        }

        return $subject;
    }

    /**
     * Видит ли пользователь документ: открытый — всем, кто видит событие или
     * задачу; закрытый — только загрузившему и управляющим.
     */
    private function canSeeDocument(Document $document): bool
    {
        $subject = $this->subjectOf($document);

        if (!$this->canView($subject)) {
            return false;
        }

        if (!$document->is_private) {
            return true;
        }

        return $document->uploaded_by_user_id === Auth::id() || $this->isManager();
    }

    /**
     * Видит ли пользователь само событие или задачу.
     */
    private function canView(CalendarEvent|CalendarTask $subject): bool
    {
        $user = Auth::user();

        if ($this->isManager()) {
            return true;
        }

        if ($subject instanceof CalendarEvent) {
            return $subject->organizer_id === $user->id
                || $subject->participants()->where("user_id", $user->id)->exists();
        }

        return $subject->user_id === $user->id || $subject->created_by_user_id === $user->id;
    }

    private function isManager(): bool
    {
        return Auth::user()->hasRole(["admin", "master"]);
    }
}
