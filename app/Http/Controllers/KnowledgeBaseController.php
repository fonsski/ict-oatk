<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeBase;
use App\Models\KnowledgeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
// Parsedown optional; if not installed we'll fallback to simple rendering
use Parsedown;
use HTMLPurifier;
use HTMLPurifier_Config;

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
                !auth()->check() ||
                !auth()
                    ->user()
                    ->hasRole(["admin", "master", "technician"])
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

        // Если есть параметр поиска
        if ($request->filled("search")) {
            $searchQuery = $request->get("search");
            $query
                ->where("title", "like", "%{$searchQuery}%")
                ->orWhere("content", "like", "%{$searchQuery}%");
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
    public function store(Request $request)
    {
        $data = $request->validate([
            "title" => "required|string|max:255",
            "category_id" => "required|exists:knowledge_categories,id",
            "description" => "nullable|string|max:1000",
            "content" => "required|string",
            "tags" => "nullable|string|max:255",
        ]);

        $article = new KnowledgeBase();
        $article->title = $data["title"];
        $article->slug = Str::slug($data["title"]);
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

        $article->tags = $data["tags"] ?? null;
        $article->author_id = Auth::id();
        $article->published_at = now();
        $article->save();

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
    public function update(Request $request, KnowledgeBase $knowledge)
    {
        $data = $request->validate([
            "title" => "required|string|max:255",
            "category_id" => "required|exists:knowledge_categories,id",
            "description" => "nullable|string|max:1000",
            "content" => "required|string",
            "tags" => "nullable|string|max:255",
        ]);

        $knowledge->title = $data["title"];
        $knowledge->slug = Str::slug($data["title"]);
        $knowledge->category_id = $data["category_id"];
        $knowledge->excerpt = $data["description"] ?? null;
        $knowledge->markdown = $data["content"];

        if (class_exists(Parsedown::class)) {
            $pd = new Parsedown();
            $knowledge->content = $pd->text($data["content"]);
        } else {
            $knowledge->content = "<p>" . nl2br(e($data["content"])) . "</p>";
        }

        $knowledge->tags = $data["tags"] ?? null;
        $knowledge->save();

        return redirect()
            ->route("knowledge.show", $knowledge)
            ->with("success", "Статья обновлена");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KnowledgeBase $knowledge)
    {
        $knowledge->delete();
        return redirect()
            ->route("knowledge.index")
            ->with("success", "Статья удалена");
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
                "p,strong,em,ul,ol,li,br,pre,code,h1,h2,h3,h4,blockquote,a[href],img[src|alt|width|height]",
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
            "<p><a><strong><em><ul><ol><li><br><pre><code><h1><h2><h3><h4><blockquote><img>";
        $clean = strip_tags($html, $allowed);

        return $clean;
    }
}
