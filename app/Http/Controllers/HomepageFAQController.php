<?php

namespace App\Http\Controllers;

use App\Models\HomepageFAQ;
use App\Http\Requests\StoreHomepageFAQRequest;
use App\Http\Requests\UpdateHomepageFAQRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Parsedown;

class HomepageFAQController extends Controller
{
    public function __construct()
    {
        
    }

    
     * Check if user has admin or master role

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

    
     * Display a listing of the resource.

    public function index()
    {
        $this->checkPermission();

        $faqs = HomepageFAQ::with("author")->ordered()->paginate(15);

        return view("homepage-faq.index", compact("faqs"));
    }

    
     * Show the form for creating a new resource.

    public function create()
    {
        $this->checkPermission();

        return view("homepage-faq.create");
    }

    
     * Store a newly created resource in storage.

    public function store(StoreHomepageFAQRequest $request)
    {
        $this->checkPermission();

        $data = $request->validated();

        $faq = new HomepageFAQ();
        $faq->title = $data["title"];
        $faq->slug = Str::slug($data["title"]);
        $faq->excerpt = $data["excerpt"] ?? null;
        $faq->markdown = $data["content"];

        
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

    
     * Display the specified resource.

    public function show($slug)
    {
        $faq = HomepageFAQ::where("slug", $slug)->active()->firstOrFail();

        return view("homepage-faq.show", compact("faq"));
    }

    
     * Show the form for editing the specified resource.

    public function edit(HomepageFAQ $homepageFaq)
    {
        $this->checkPermission();

        return view("homepage-faq.edit", ["faq" => $homepageFaq]);
    }

    
     * Update the specified resource in storage.

    public function update(UpdateHomepageFAQRequest $request, HomepageFAQ $homepageFaq)
    {
        $this->checkPermission();

        $data = $request->validated();

        $homepageFaq->title = $data["title"];
        $homepageFaq->slug = Str::slug($data["title"]);
        $homepageFaq->excerpt = $data["excerpt"] ?? null;
        $homepageFaq->markdown = $data["content"];

        
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

    
     * Remove the specified resource from storage.

    public function destroy(HomepageFAQ $homepageFaq)
    {
        $this->checkPermission();

        $homepageFaq->delete();

        return redirect()
            ->route("homepage-faq.index")
            ->with("success", "FAQ успешно удален");
    }

    
     * Toggle active status

    public function toggleActive(HomepageFAQ $homepageFaq)
    {
        $this->checkPermission();

        $homepageFaq->is_active = !$homepageFaq->is_active;
        $homepageFaq->save();

        $status = $homepageFaq->is_active ? "активирован" : "деактивирован";
        return back()->with("success", "FAQ {$status}");
    }

    
     * Preview markdown content

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

    
     * Handle image uploads for FAQ content

    public function uploadImage(Request $request)
    {
        $this->checkPermission();

        
        $request->headers->set("Accept", "application/json");

        
        try {
            $validated = $request->validate([
                "image" => "required|image|max:5120", 
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Ошибка валидации: " .
                        implode(
                            ", ",
                            $e->errors()["image"] ?? ["Неверное изображение"],
                        ),
                ],
                422,
            );
        }

        try {
            
            $file = $request->file("image");

            \Log::info("Попытка загрузки изображения", [
                "file_exists" => (bool) $file,
                "original_name" => $file
                    ? $file->getClientOriginalName()
                    : null,
                "size" => $file ? $file->getSize() : null,
                "mime" => $file ? $file->getMimeType() : null,
            ]);

            if (!$file || !$file->isValid()) {
                \Log::error("Файл недействителен", [
                    "is_valid" => $file ? $file->isValid() : false,
                    "error" => $file ? $file->getError() : "Файл не найден",
                ]);
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "Загруженный файл недействителен или поврежден",
                    ],
                    400,
                );
            }

            
            $extension = $file->getClientOriginalExtension();
            $filename =
                Str::slug(
                    pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                ) .
                "_" .
                Str::random(10) .
                "." .
                $extension;

            
            $directory = "public/faq/images";
            $storagePath = storage_path("app/{$directory}");
            \Log::info("Проверка директории для загрузки", [
                "directory" => $directory,
                "storage_path" => $storagePath,
                "exists" => file_exists($storagePath),
            ]);

            if (!file_exists($storagePath)) {
                try {
                    File::makeDirectory($storagePath, 0755, true, true);
                    \Log::info("Директория создана успешно");
                } catch (\Exception $e) {
                    \Log::error("Ошибка создания директории", [
                        "error" => $e->getMessage(),
                        "trace" => $e->getTraceAsString(),
                    ]);
                    throw new \Exception(
                        "Не удалось создать директорию для загрузки: " .
                            $e->getMessage(),
                    );
                }
            }

            
            try {
                $path = Storage::putFileAs($directory, $file, $filename);
                \Log::info("Файл успешно сохранен", [
                    "path" => $path,
                    "full_path" => Storage::path($path),
                ]);
            } catch (\Exception $e) {
                \Log::error("Ошибка сохранения файла", [
                    "error" => $e->getMessage(),
                    "trace" => $e->getTraceAsString(),
                ]);
                throw new \Exception(
                    "Ошибка при сохранении файла: " . $e->getMessage(),
                );
            }

            
            $url = url("storage/" . str_replace("public/", "", $path));
            return response()->json([
                "success" => true,
                "url" => $url,
                "markdown" =>
                    "![" . $file->getClientOriginalName() . "](" . $url . ")",
            ]);
        } catch (\Exception $e) {
            
            \Log::error("Image upload error in FAQ", [
                "error" => $e->getMessage(),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
                "trace" => $e->getTraceAsString(),
                "request_data" => $request->all(),
                "request_headers" => $request->headers->all(),
                "php_version" => PHP_VERSION,
                "storage_permissions" => [
                    "public_writable" => is_writable(
                        storage_path("app/public"),
                    ),
                    "storage_writable" => is_writable(storage_path()),
                ],
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Ошибка загрузки изображения: " . $e->getMessage(),
                    "debug_info" => [
                        "php_version" => PHP_VERSION,
                        "storage_path" => storage_path("app/public"),
                        "is_writable" => is_writable(
                            storage_path("app/public"),
                        ),
                    ],
                ],
                500,
            );
        }
    }

    
     * Sanitize HTML output

    private function sanitizeHtml(string $html): string
    {
        if (class_exists("\HTMLPurifier")) {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set(
                "HTML.Allowed",
                "p,strong,em,ul,ol,li,br,pre,code,h1,h2,h3,h4,h5,h6,blockquote,a[href|title|target],img[src|alt|width|height|class|style]",
            );
            $purifier = new \HTMLPurifier($config);
            return $purifier->purify($html);
        }

        
        $html = preg_replace('/on[a-z]+\s*=\s*"[^"]*"/i', "", $html);
        $html = preg_replace('/on[a-z]+\s*=\s*\'[^\']*\'/i', "", $html);
        $html = preg_replace(
            '/(href|src)\s*=\s*"javascript:[^\"]*"/i',
            '$1="
            $html,
        );
        $html = preg_replace(
            '/(href|src)\s*=\s*\'javascript:[^\']*\'/i',
            '$1="
            $html,
        );

        $allowed =
            "<p><a><strong><em><ul><ol><li><br><pre><code><h1><h2><h3><h4><h5><h6><blockquote><img><div><span>";
        return strip_tags($html, $allowed);
    }
}
