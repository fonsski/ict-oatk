<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeBase;
use App\Models\KnowledgeCategory;
use App\Events\KnowledgeBaseArticleCreated;
use App\Events\KnowledgeBaseArticleUpdated;
use App\Http\Requests\StoreKnowledgeBaseRequest;
use App\Http\Requests\UpdateKnowledgeBaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
// Parsedown optional; if not installed we'll fallback to simple rendering
use Parsedown;
use HTMLPurifier;
use HTMLPurifier_Config;
use App\Models\KnowledgeImage;

class KnowledgeBaseController extends Controller
{
    /**
     * Конструктор контроллера
     */
    public function __construct()
    {
        // Проверка роли для всех методов
        $this->middleware(function ($request, $next) {
            if (
                !Auth::check() ||
                !Auth::user()->hasRole(["admin", "master", "technician"])
            ) {
                abort(403, "У вас нет прав для доступа к базе знаний.");
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Вывод списка статей базы знаний
        $query = KnowledgeBase::with("category", "author");

        // Фильтр по категории
        if ($request->filled("category")) {
            $query->where("category_id", $request->get("category"));
        }

        // Если есть параметр поиска. Условия title/content группируем,
        // иначе orWhere ломает фильтр по категории (category = X AND title
        // OR content → возвращает статьи из других категорий).
        if ($request->filled("search")) {
            $searchQuery = $request->get("search");
            $query->where(function ($q) use ($searchQuery) {
                $q->where("title", "like", "%{$searchQuery}%")
                    ->orWhere("content", "like", "%{$searchQuery}%")
                    ->orWhere("tags", "like", "%{$searchQuery}%");
            });
        }

        $articles = $query->latest()->paginate(10);
        $categories = KnowledgeCategory::active()->ordered()->get();

        return view("knowledge.index", compact("articles", "categories"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Форма создания статьи
        $categories = KnowledgeCategory::active()->ordered()->get();
        return view("knowledge.create", compact("categories"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreKnowledgeBaseRequest $request)
    {
        $data = $request->validated();

        $article = new KnowledgeBase();
        $article->title = $data["title"];
        $article->slug = $this->uniqueSlug($data["title"]);
        $article->category_id = $data["category_id"];
        $article->excerpt = $data["description"] ?? null;
        $article->markdown = $data["content"];

        if (class_exists(Parsedown::class)) {
            $pd = new Parsedown();
            $html = $pd->text($data["content"]);
        } else {
            $html = "<p>" . nl2br(e($data["content"])) . "</p>";
        }

        $article->content = $this->sanitizeHtml($html);

        // Обрабатываем теги, убираем символы # если они есть и удаляем дубликаты
        if (isset($data["tags"])) {
            $tags = explode(",", $data["tags"]);
            $cleanTags = [];
            foreach ($tags as $tag) {
                $tag = trim($tag);
                // Удаляем символ # в начале тега, если он есть
                if (substr($tag, 0, 1) === "#") {
                    $tag = substr($tag, 1);
                }
                if (!empty($tag)) {
                    $cleanTags[] = $tag;
                }
            }
            // Удаляем дубликаты, сохраняя порядок
            $cleanTags = array_unique($cleanTags);
            $article->tags = implode(", ", $cleanTags);
        } else {
            $article->tags = null;
        }
        $article->author_id = Auth::id();
        $article->published_at = now();
        $article->save();

        // Отправляем событие о создании статьи
        event(new KnowledgeBaseArticleCreated($article, Auth::user()));

        return redirect()
            ->route("knowledge.show", $article)
            ->with("success", "Статья создана");
    }

    /**
     * Display the specified resource.
     */
    public function show(KnowledgeBase $knowledge)
    {
        // Просмотр отдельной статьи
        $article = $knowledge;

        // Счётчик просмотров: увеличиваем атомарно, без изменения updated_at.
        $article->incrementQuietly("views_count");

        $article->load("category", "author");

        $relatedArticles = KnowledgeBase::with("category")
            ->where("category_id", $article->category_id)
            ->where("id", "!=", $article->id)
            ->latest()
            ->take(4)
            ->get();

        $article->load("images");

        // If content is empty (for records created outside controller), render markdown and sanitize
        if (empty($article->content) && !empty($article->markdown)) {
            if (class_exists(\Parsedown::class)) {
                $pd = new \Parsedown();
                $html = $pd->text($article->markdown);
            } else {
                $html = "<p>" . nl2br(e($article->markdown)) . "</p>";
            }

            $article->content = $this->sanitizeHtml($html);
        }

        return view("knowledge.show", compact("article", "relatedArticles"));
    }

    /**
     * Preview markdown via AJAX (returns sanitized HTML)
     */
    public function preview(Request $request)
    {
        $request->validate([
            "content" => "required|string",
        ]);

        $markdown = $request->input("content");
        if (class_exists(Parsedown::class)) {
            $pd = new Parsedown();
            $html = $pd->text($markdown);
        } else {
            $html = "<p>" . nl2br(e($markdown)) . "</p>";
        }

        $clean = $this->sanitizeHtml($html);

        return response()->json(["html" => $clean]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(KnowledgeBase $knowledge)
    {
        $categories = KnowledgeCategory::active()->ordered()->get();
        return view("knowledge.edit", [
            "article" => $knowledge,
            "categories" => $categories,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateKnowledgeBaseRequest $request, KnowledgeBase $knowledge)
    {
        $data = $request->validated();

        $knowledge->title = $data["title"];
        $knowledge->slug = $this->uniqueSlug($data["title"], $knowledge->id);
        $knowledge->category_id = $data["category_id"];
        $knowledge->excerpt = $data["description"] ?? null;
        $knowledge->markdown = $data["content"];

        if (class_exists(Parsedown::class)) {
            $pd = new Parsedown();
            $html = $pd->text($data["content"]);
        } else {
            $html = "<p>" . nl2br(e($data["content"])) . "</p>";
        }

        // Санитизация обязательна: content выводится через {!! !!} в шаблоне
        $knowledge->content = $this->sanitizeHtml($html);

        // Обрабатываем теги, убираем символы # если они есть и удаляем дубликаты
        if (isset($data["tags"])) {
            $tags = explode(",", $data["tags"]);
            $cleanTags = [];
            foreach ($tags as $tag) {
                $tag = trim($tag);
                // Удаляем символ # в начале тега, если он есть
                if (substr($tag, 0, 1) === "#") {
                    $tag = substr($tag, 1);
                }
                if (!empty($tag)) {
                    $cleanTags[] = $tag;
                }
            }
            // Удаляем дубликаты, сохраняя порядок
            $cleanTags = array_unique($cleanTags);
            $knowledge->tags = implode(", ", $cleanTags);
        } else {
            $knowledge->tags = null;
        }
        $knowledge->save();

        // Отправляем событие об обновлении статьи
        event(new KnowledgeBaseArticleUpdated($knowledge, Auth::user()));

        return redirect()
            ->route("knowledge.show", $knowledge)
            ->with("success", "Статья обновлена");
    }

    /**
     * Remove the specified resource from storage (мягкое удаление).
     */
    public function destroy(KnowledgeBase $knowledge)
    {
        $knowledge->delete();
        return redirect()
            ->route("knowledge.index")
            ->with("success", "Статья перемещена в корзину");
    }

    /**
     * Корзина: мягко удалённые статьи. Только admin и master.
     */
    public function trashed()
    {
        $this->authorizeTrashAccess();

        $articles = KnowledgeBase::onlyTrashed()
            ->with("category", "author")
            ->orderByDesc("deleted_at")
            ->paginate(10);

        return view("knowledge.trashed", compact("articles"));
    }

    /**
     * Восстановление статьи из корзины.
     */
    public function restore(KnowledgeBase $knowledge)
    {
        $this->authorizeTrashAccess();

        if (!$knowledge->trashed()) {
            return redirect()
                ->route("knowledge.trashed")
                ->with("error", "Статья не находится в корзине");
        }

        $knowledge->restore();

        return redirect()
            ->route("knowledge.show", $knowledge)
            ->with("success", "Статья восстановлена");
    }

    /**
     * Безвозвратное удаление статьи вместе с изображениями. Только admin.
     */
    public function forceDelete(KnowledgeBase $knowledge)
    {
        $user = Auth::user();
        if (!$user || !$user->role || $user->role->slug !== "admin") {
            abort(403);
        }

        if (!$knowledge->trashed()) {
            return redirect()
                ->route("knowledge.trashed")
                ->with("error", "Сначала переместите статью в корзину");
        }

        // Удаляем связанные файлы изображений с диска и записи о них.
        foreach ($knowledge->images as $image) {
            \Illuminate\Support\Facades\Storage::disk("public")->delete(
                $image->path,
            );
            $image->delete();
        }

        $knowledge->forceDelete();

        return redirect()
            ->route("knowledge.trashed")
            ->with("success", "Статья удалена безвозвратно");
    }

    /**
     * Доступ к корзине только у управляющих ролей.
     */
    private function authorizeTrashAccess(): void
    {
        $user = Auth::user();
        if (
            !$user ||
            !$user->role ||
            !in_array($user->role->slug, ["admin", "master"])
        ) {
            abort(403);
        }
    }

    /**
     * Handle image uploads for knowledge base articles
     */
    public function uploadImage(Request $request)
    {
        // Всегда отвечаем JSON — редактор ждёт JSON и на ошибках тоже.
        $request->headers->set("Accept", "application/json");

        try {
            $request->validate([
                "image" => "required|image|max:5120", // до 5 МБ
                "article_id" => "nullable|exists:knowledge_bases,id",
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        $e->errors()["image"][0] ?? "Неверное изображение",
                ],
                422,
            );
        }

        try {
            $file = $request->file("image");

            // Уникальное имя на основе исходного, чтобы не перезатирать файлы.
            $filename =
                Str::slug(
                    pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                ) .
                "_" .
                Str::random(10) .
                "." .
                $file->getClientOriginalExtension();

            // Кладём на публичный диск (storage/app/public). Для отдачи по
            // /storage нужен симлинк: php artisan storage:link.
            $path = $file->storeAs(
                "knowledge/images",
                $filename,
                "public",
            );

            if ($request->filled("article_id")) {
                KnowledgeImage::create([
                    "knowledge_base_id" => $request->input("article_id"),
                    "path" => $path,
                    "alt" => $file->getClientOriginalName(),
                ]);
            }

            // Корень-относительный URL: не зависит от APP_URL и хоста,
            // корректно работает при встраивании в тело статьи.
            $url = "/storage/" . $path;

            return response()->json([
                "success" => true,
                "url" => $url,
                "markdown" =>
                    "![" . $file->getClientOriginalName() . "](" . $url . ")",
            ]);
        } catch (\Exception $e) {
            Log::error("Ошибка загрузки изображения в базу знаний", [
                "error" => $e->getMessage(),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" => "Ошибка загрузки изображения",
                ],
                500,
            );
        }
    }

    /**
     * Генерирует уникальный slug на основе заголовка. При коллизии
     * добавляет числовой суффикс (-2, -3, ...). $ignoreId исключает
     * саму статью при обновлении.
     */
    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: "article";
        $slug = $base;
        $suffix = 2;

        while (
            KnowledgeBase::withTrashed()
                ->where("slug", $slug)
                ->when($ignoreId, fn($q) => $q->where("id", "!=", $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * Sanitize HTML output using HTMLPurifier if available, otherwise fallback to simple filtering.
     */
    private function sanitizeHtml(string $html): string
    {
        // Prefer HTMLPurifier when available
        if (class_exists("\HTMLPurifier")) {
            $config = \HTMLPurifier_Config::createDefault();
            // Allow basic elements and safe attributes
            $config->set(
                "HTML.Allowed",
                "p,strong,em,ul,ol,li,br,pre,code,h1,h2,h3,h4,blockquote,a[href|title|target],img[src|alt|width|height|class|style]",
            );
            $purifier = new \HTMLPurifier($config);
            return $purifier->purify($html);
        }

        // Fallback: remove event handlers and javascript: links, then strip to allowed tags
        // Remove on* attributes
        $html = preg_replace('/on[a-z]+\s*=\s*"[^"]*"/i', "", $html);
        $html = preg_replace('/on[a-z]+\s*=\s*\'[^\']*\'/i', "", $html);
        // Remove javascript: in href/src
        $html = preg_replace(
            '/(href|src)\s*=\s*"javascript:[^\"]*"/i',
            '$1="#"',
            $html,
        );
        $html = preg_replace(
            '/(href|src)\s*=\s*\'javascript:[^\']*\'/i',
            '$1="#"',
            $html,
        );

        // Allow a conservative set of tags
        $allowed =
            "<p><a><strong><em><ul><ol><li><br><pre><code><h1><h2><h3><h4><blockquote><img><div><span>";
        $clean = strip_tags($html, $allowed);

        return $clean;
    }
}
