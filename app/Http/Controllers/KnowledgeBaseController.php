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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

use Parsedown;
use HTMLPurifier;
use HTMLPurifier_Config;
use App\Models\KnowledgeImage;

class KnowledgeBaseController extends Controller
{
    
     * Конструктор контроллера

    public function __construct()
    {
        
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

    
     * Display a listing of the resource.

    public function index(Request $request)
    {
        
        $query = KnowledgeBase::with("category", "author");

        
        if ($request->filled("category")) {
            $query->where("category_id", $request->get("category"));
        }

        
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

    
     * Show the form for creating a new resource.

    public function create()
    {
        
        $categories = KnowledgeCategory::active()->ordered()->get();
        return view("knowledge.create", compact("categories"));
    }

    
     * Store a newly created resource in storage.

    public function store(StoreKnowledgeBaseRequest $request)
    {
        $data = $request->validated();

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

        
        if (isset($data["tags"])) {
            $tags = explode(",", $data["tags"]);
            $cleanTags = [];
            foreach ($tags as $tag) {
                $tag = trim($tag);
                
                if (substr($tag, 0, 1) === "
                    $tag = substr($tag, 1);
                }
                if (!empty($tag)) {
                    $cleanTags[] = $tag;
                }
            }
            
            $cleanTags = array_unique($cleanTags);
            $article->tags = implode(", ", $cleanTags);
        } else {
            $article->tags = null;
        }
        $article->author_id = Auth::id();
        $article->published_at = now();
        $article->save();

        
        event(new KnowledgeBaseArticleCreated($article, Auth::user()));

        return redirect()
            ->route("knowledge.show", $article)
            ->with("success", "Статья создана");
    }

    
     * Display the specified resource.

    public function show(KnowledgeBase $knowledge)
    {
        
        $article = $knowledge;
        $article->load("category", "author");

        $relatedArticles = KnowledgeBase::with("category")
            ->where("category_id", $article->category_id)
            ->where("id", "!=", $article->id)
            ->latest()
            ->take(4)
            ->get();

        $article->load("images");

        
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

    
     * Preview markdown via AJAX (returns sanitized HTML)

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

    
     * Show the form for editing the specified resource.

    public function edit(KnowledgeBase $knowledge)
    {
        $categories = KnowledgeCategory::active()->ordered()->get();
        return view("knowledge.edit", [
            "article" => $knowledge,
            "categories" => $categories,
        ]);
    }

    
     * Update the specified resource in storage.

    public function update(UpdateKnowledgeBaseRequest $request, KnowledgeBase $knowledge)
    {
        $data = $request->validated();

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

        
        if (isset($data["tags"])) {
            $tags = explode(",", $data["tags"]);
            $cleanTags = [];
            foreach ($tags as $tag) {
                $tag = trim($tag);
                
                if (substr($tag, 0, 1) === "
                    $tag = substr($tag, 1);
                }
                if (!empty($tag)) {
                    $cleanTags[] = $tag;
                }
            }
            
            $cleanTags = array_unique($cleanTags);
            $knowledge->tags = implode(", ", $cleanTags);
        } else {
            $knowledge->tags = null;
        }
        $knowledge->save();

        
        event(new KnowledgeBaseArticleUpdated($knowledge, Auth::user()));

        return redirect()
            ->route("knowledge.show", $knowledge)
            ->with("success", "Статья обновлена");
    }

    
     * Remove the specified resource from storage.

    public function destroy(KnowledgeBase $knowledge)
    {
        $knowledge->delete();
        return redirect()
            ->route("knowledge.index")
            ->with("success", "Статья удалена");
    }

    
     * Handle image uploads for knowledge base articles

    public function uploadImage(Request $request)
    {
        
        $request->headers->set("Accept", "application/json");

        
        try {
            $validated = $request->validate([
                "image" => "required|image|max:5120", 
                "article_id" => "nullable|exists:knowledge_bases,id",
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

            
            $directory = "public/knowledge/images";
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
                
                $uploadDir = storage_path("app/{$directory}");
                if (!file_exists($uploadDir)) {
                    echo "Директория {$uploadDir} не существует, пытаемся создать... ";
                    if (!mkdir($uploadDir, 0755, true)) {
                        echo "Не удалось создать директорию {$uploadDir}";
                        throw new \Exception(
                            "Не удалось создать директорию {$uploadDir}",
                        );
                    }
                    echo "Директория создана успешно";
                }

                
                if (!is_writable($uploadDir)) {
                    echo "Директория {$uploadDir} недоступна для записи";
                    throw new \Exception(
                        "Директория {$uploadDir} недоступна для записи",
                    );
                }

                
                $uploadPath = $uploadDir . "/" . $filename;
                if (move_uploaded_file($file->getPathname(), $uploadPath)) {
                    $path = $directory . "/" . $filename;
                    \Log::info("Файл успешно сохранен", [
                        "path" => $path,
                        "full_path" => $uploadPath,
                    ]);
                } else {
                    throw new \Exception(
                        "Не удалось переместить загруженный файл",
                    );
                }
            } catch (\Exception $e) {
                \Log::error("Ошибка сохранения файла", [
                    "error" => $e->getMessage(),
                    "trace" => $e->getTraceAsString(),
                ]);
                throw new \Exception(
                    "Ошибка при сохранении файла: " . $e->getMessage(),
                );
            }

            
            if ($request->filled("article_id")) {
                KnowledgeImage::create([
                    "knowledge_base_id" => $request->input("article_id"),
                    "path" => $path,
                    "alt" => $file->getClientOriginalName(),
                ]);
            }

            
            $url = url("storage/" . str_replace("public/", "", $path));
            return response()->json([
                "success" => true,
                "url" => $url,
                "markdown" =>
                    "![" . $file->getClientOriginalName() . "](" . $url . ")",
            ]);
        } catch (\Exception $e) {
            
            \Log::error("Image upload error", [
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

            
            echo "<pre>";
            echo "Ошибка загрузки изображения: " . $e->getMessage() . "\n";
            echo "Файл: " . $e->getFile() . "\n";
            echo "Строка: " . $e->getLine() . "\n";
            echo "Трассировка: " . $e->getTraceAsString() . "\n";
            echo "PHP версия: " . PHP_VERSION . "\n";
            echo "Путь к storage: " . storage_path("app/public") . "\n";
            echo "Права доступа: " .
                (is_writable(storage_path("app/public"))
                    ? "Доступно для записи"
                    : "Недоступно для записи") .
                "\n";

            if (isset($file)) {
                echo "Информация о файле:\n";
                echo "  Имя: " . $file->getClientOriginalName() . "\n";
                echo "  Размер: " . $file->getSize() . " байт\n";
                echo "  MIME тип: " . $file->getMimeType() . "\n";
                echo "  Ошибка загрузки: " . $file->getError() . "\n";
            }

            die();
            

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

    
     * Sanitize HTML output using HTMLPurifier if available, otherwise fallback to simple filtering.

    private function sanitizeHtml(string $html): string
    {
        
        if (class_exists("\HTMLPurifier")) {
            $config = \HTMLPurifier_Config::createDefault();
            
            $config->set(
                "HTML.Allowed",
                "p,strong,em,ul,ol,li,br,pre,code,h1,h2,h3,h4,blockquote,a[href|title|target],img[src|alt|width|height|class|style]",
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
            "<p><a><strong><em><ul><ol><li><br><pre><code><h1><h2><h3><h4><blockquote><img><div><span>";
        $clean = strip_tags($html, $allowed);

        return $clean;
    }
}
