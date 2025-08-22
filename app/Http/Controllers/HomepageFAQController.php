<?php

namespace App\Http\Controllers;

use App\Models\HomepageFAQ;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Parsedown;

class HomepageFAQController extends Controller
{
    public function __construct()
    {
        // Middleware будет применяться через маршруты
    }

    /**
     * Check if user has admin or master role
     */
    private function checkPermission()
    {
        if (
            !auth()->check() ||
            (!auth()->user()->hasRole("admin") &&
                !auth()->user()->hasRole("master"))
        ) {
            abort(403, "Недостаточно прав доступа");
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->checkPermission();

        $faqs = HomepageFAQ::with("author")->ordered()->paginate(15);

        return view("homepage-faq.index", compact("faqs"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->checkPermission();

        return view("homepage-faq.create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->checkPermission();

        $data = $request->validate([
            "title" => "required|string|max:255",
            "excerpt" => "nullable|string|max:500",
            "content" => "required|string",
            "is_active" => "boolean",
            "sort_order" => "nullable|integer|min:0",
        ]);

        $faq = new HomepageFAQ();
        $faq->title = $data["title"];
        $faq->slug = Str::slug($data["title"]);
        $faq->excerpt = $data["excerpt"] ?? null;
        $faq->markdown = $data["content"];

        // Конвертируем markdown в HTML
        if (class_exists(Parsedown::class)) {
            $parsedown = new Parsedown();
            $html = $parsedown->text($data["content"]);
        } else {
            $html = "<p>" . nl2br(e($data["content"])) . "</p>";
        }

        $faq->content = $this->sanitizeHtml($html);
        $faq->is_active = $data["is_active"] ?? true;
        $faq->sort_order = $data["sort_order"] ?? 0;
        $faq->author_id = Auth::id();

        $faq->save();

        return redirect()
            ->route("homepage-faq.index")
            ->with("success", "FAQ успешно создан");
    }

    /**
     * Display the specified resource.
     */
    public function show($slug)
    {
        $faq = HomepageFAQ::where("slug", $slug)->active()->firstOrFail();

        return view("homepage-faq.show", compact("faq"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(HomepageFAQ $homepageFaq)
    {
        $this->checkPermission();

        return view("homepage-faq.edit", ["faq" => $homepageFaq]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, HomepageFAQ $homepageFaq)
    {
        $this->checkPermission();

        $data = $request->validate([
            "title" => "required|string|max:255",
            "excerpt" => "nullable|string|max:500",
            "content" => "required|string",
            "is_active" => "boolean",
            "sort_order" => "nullable|integer|min:0",
        ]);

        $homepageFaq->title = $data["title"];
        $homepageFaq->slug = Str::slug($data["title"]);
        $homepageFaq->excerpt = $data["excerpt"] ?? null;
        $homepageFaq->markdown = $data["content"];

        // Конвертируем markdown в HTML
        if (class_exists(Parsedown::class)) {
            $parsedown = new Parsedown();
            $html = $parsedown->text($data["content"]);
        } else {
            $html = "<p>" . nl2br(e($data["content"])) . "</p>";
        }

        $homepageFaq->content = $this->sanitizeHtml($html);
        $homepageFaq->is_active = $data["is_active"] ?? true;
        $homepageFaq->sort_order = $data["sort_order"] ?? 0;

        $homepageFaq->save();

        return redirect()
            ->route("homepage-faq.index")
            ->with("success", "FAQ успешно обновлен");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HomepageFAQ $homepageFaq)
    {
        $this->checkPermission();

        $homepageFaq->delete();

        return redirect()
            ->route("homepage-faq.index")
            ->with("success", "FAQ успешно удален");
    }

    /**
     * Toggle active status
     */
    public function toggleActive(HomepageFAQ $homepageFaq)
    {
        $this->checkPermission();

        $homepageFaq->is_active = !$homepageFaq->is_active;
        $homepageFaq->save();

        $status = $homepageFaq->is_active ? "активирован" : "деактивирован";
        return back()->with("success", "FAQ {$status}");
    }

    /**
     * Preview markdown content
     */
    public function preview(Request $request)
    {
        $this->checkPermission();

        $request->validate([
            "content" => "required|string",
        ]);

        $markdown = $request->input("content");

        if (class_exists(Parsedown::class)) {
            $parsedown = new Parsedown();
            $html = $parsedown->text($markdown);
        } else {
            $html = "<p>" . nl2br(e($markdown)) . "</p>";
        }

        $clean = $this->sanitizeHtml($html);

        return response()->json(["html" => $clean]);
    }

    /**
     * Sanitize HTML output
     */
    private function sanitizeHtml(string $html): string
    {
        if (class_exists("\HTMLPurifier")) {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set(
                "HTML.Allowed",
                "p,strong,em,ul,ol,li,br,pre,code,h1,h2,h3,h4,h5,h6,blockquote,a[href],img[src|alt|width|height]",
            );
            $purifier = new \HTMLPurifier($config);
            return $purifier->purify($html);
        }

        // Fallback: простая очистка
        $html = preg_replace('/on[a-z]+\s*=\s*"[^"]*"/i', "", $html);
        $html = preg_replace('/on[a-z]+\s*=\s*\'[^\']*\'/i', "", $html);
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

        $allowed =
            "<p><a><strong><em><ul><ol><li><br><pre><code><h1><h2><h3><h4><h5><h6><blockquote><img>";
        return strip_tags($html, $allowed);
    }
}
