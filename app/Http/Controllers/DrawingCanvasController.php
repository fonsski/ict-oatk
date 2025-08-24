<?php

namespace App\Http\Controllers;

use App\Models\DrawingCanvas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DrawingCanvasController extends Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Check role for all methods
        $this->middleware(function ($request, $next) {
            if (
                !auth()->check() ||
                !in_array(auth()->user()->role->slug, [
                    "admin",
                    "technician",
                    "master",
                ])
            ) {
                abort(403, "Unauthorized action.");
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of drawings
     */
    public function index()
    {
        try {
            $drawings = DrawingCanvas::with("author")->latest()->paginate(10);
            return view("drawing-canvas.index", compact("drawings"));
        } catch (\Exception $e) {
            // Fallback to a simple view if something goes wrong
            \Log::error("Error in DrawingCanvasController::index", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            return response()->view(
                "errors.custom",
                [
                    "title" => "Инструмент для рисования",
                    "message" =>
                        "Инструмент для рисования находится в разработке. Пожалуйста, попробуйте позже.",
                    "error" => $e->getMessage(),
                    "trace" => config("app.debug")
                        ? $e->getTraceAsString()
                        : null,
                ],
                200,
            );
        }
    }

    /**
     * Show the form for creating a new drawing
     */
    public function create()
    {
        return view("drawing-canvas.create");
    }

    /**
     * Store a newly created drawing
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            "title" => "required|string|max:255",
            "description" => "nullable|string|max:1000",
            "canvas_data" => "required|json",
            "type" => "nullable|string|max:50",
        ]);

        $drawing = new DrawingCanvas();
        $drawing->title = $data["title"];
        $drawing->slug = Str::slug($data["title"]);
        $drawing->description = $data["description"] ?? null;
        $drawing->canvas_data = $data["canvas_data"];
        $drawing->type = $data["type"] ?? "general";
        $drawing->author_id = Auth::id();
        $drawing->save();

        return redirect()
            ->route("drawing-canvas.show", $drawing)
            ->with("success", "Drawing created successfully");
    }

    /**
     * Display the specified drawing
     */
    public function show(DrawingCanvas $drawing_canvas)
    {
        return view("drawing-canvas.show", [
            "drawing" => $drawing_canvas,
        ]);
    }

    /**
     * Show the form for editing the drawing
     */
    public function edit(DrawingCanvas $drawing_canvas)
    {
        // Check if user is allowed to edit this drawing
        if (
            Auth::id() !== $drawing_canvas->author_id &&
            !Auth::user()->isAdmin()
        ) {
            abort(403, "You are not authorized to edit this drawing");
        }

        return view("drawing-canvas.edit", [
            "drawing" => $drawing_canvas,
        ]);
    }

    /**
     * Update the specified drawing
     */
    public function update(Request $request, DrawingCanvas $drawing_canvas)
    {
        // Check if user is allowed to edit this drawing
        if (
            Auth::id() !== $drawing_canvas->author_id &&
            !Auth::user()->isAdmin()
        ) {
            abort(403, "You are not authorized to edit this drawing");
        }

        $data = $request->validate([
            "title" => "required|string|max:255",
            "description" => "nullable|string|max:1000",
            "canvas_data" => "required|json",
            "type" => "nullable|string|max:50",
        ]);

        $drawing_canvas->title = $data["title"];
        $drawing_canvas->slug = Str::slug($data["title"]);
        $drawing_canvas->description = $data["description"] ?? null;
        $drawing_canvas->canvas_data = $data["canvas_data"];
        $drawing_canvas->type = $data["type"] ?? "general";
        $drawing_canvas->save();

        return redirect()
            ->route("drawing-canvas.show", $drawing_canvas)
            ->with("success", "Drawing updated successfully");
    }

    /**
     * Remove the specified drawing
     */
    public function destroy(DrawingCanvas $drawing_canvas)
    {
        // Check if user is allowed to delete this drawing
        if (
            Auth::id() !== $drawing_canvas->author_id &&
            !Auth::user()->isAdmin()
        ) {
            abort(403, "You are not authorized to delete this drawing");
        }

        $drawing_canvas->delete();

        return redirect()
            ->route("drawing-canvas.index")
            ->with("success", "Drawing deleted successfully");
    }
}
