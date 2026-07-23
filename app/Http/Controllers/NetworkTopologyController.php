<?php

namespace App\Http\Controllers;

use App\Models\NetworkDiagram;
use App\Models\NetworkLink;
use App\Models\NetworkNode;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NetworkTopologyController extends Controller
{
    public function __construct()
    {
        // Строить и просматривать топологию могут все сотрудники.
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (
                !$user ||
                !$user->role ||
                !in_array($user->role->slug, ["admin", "master", "technician"])
            ) {
                abort(403, "Нет прав для доступа к топологии сети.");
            }
            return $next($request);
        });
    }

    public function index()
    {
        $diagrams = NetworkDiagram::withCount("nodes")
            ->with("author:id,name")
            ->latest()
            ->paginate(12);

        return view("topology.index", compact("diagrams"));
    }

    public function create()
    {
        return view("topology.create");
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            "name" => "required|string|min:2|max:255",
            "description" => "nullable|string|max:2000",
        ]);

        $diagram = NetworkDiagram::create([
            "name" => $data["name"],
            "description" => $data["description"] ?? null,
            "author_id" => Auth::id(),
        ]);

        return redirect()
            ->route("topology.show", $diagram)
            ->with("success", "Схема создана — можно строить топологию");
    }

    public function show(NetworkDiagram $topology)
    {
        $topology->load(["nodes.room:id,number,name", "links"]);
        $rooms = Room::orderBy("number")->get(["id", "number", "name"]);

        return view("topology.show", [
            "diagram" => $topology,
            "rooms" => $rooms,
            "types" => NetworkNode::TYPES,
        ]);
    }

    public function update(Request $request, NetworkDiagram $topology)
    {
        $data = $request->validate([
            "name" => "required|string|min:2|max:255",
            "description" => "nullable|string|max:2000",
        ]);

        $topology->update($data);

        return back()->with("success", "Схема обновлена");
    }

    public function destroy(NetworkDiagram $topology)
    {
        $topology->delete();

        return redirect()
            ->route("topology.index")
            ->with("success", "Схема удалена");
    }

    public function print(NetworkDiagram $topology)
    {
        $topology->load(["nodes.room:id,number,name", "links"]);

        return view("topology.print", [
            "diagram" => $topology,
            "types" => NetworkNode::TYPES,
        ]);
    }

    // ------------------------------------------------------------------
    // JSON-API редактора
    // ------------------------------------------------------------------

    public function storeNode(Request $request, NetworkDiagram $topology)
    {
        $data = $this->validateNode($request);

        $node = $topology->nodes()->create($data);
        $node->load("room:id,number,name");

        return response()->json($this->nodePayload($node), 201);
    }

    public function updateNode(
        Request $request,
        NetworkDiagram $topology,
        NetworkNode $node,
    ) {
        $this->ensureBelongs($topology, $node->diagram_id);

        $data = $this->validateNode($request, partial: true);
        $node->update($data);
        $node->load("room:id,number,name");

        return response()->json($this->nodePayload($node));
    }

    public function destroyNode(NetworkDiagram $topology, NetworkNode $node)
    {
        $this->ensureBelongs($topology, $node->diagram_id);

        // Связи узла уйдут каскадом на уровне БД.
        $node->delete();

        return response()->json(["status" => "ok"]);
    }

    public function storeLink(Request $request, NetworkDiagram $topology)
    {
        $ids = $topology->nodes()->pluck("id");

        $data = $request->validate([
            "source_id" => ["required", "integer", "in:" . $ids->implode(",")],
            "target_id" => [
                "required",
                "integer",
                "different:source_id",
                "in:" . $ids->implode(","),
            ],
            "label" => "nullable|string|max:255",
        ]);

        // Не создаём дубль связи между теми же узлами (в любом направлении).
        $exists = $topology
            ->links()
            ->where(function ($q) use ($data) {
                $q->where("source_id", $data["source_id"])->where(
                    "target_id",
                    $data["target_id"],
                );
            })
            ->orWhere(function ($q) use ($data, $topology) {
                $q->where("diagram_id", $topology->id)
                    ->where("source_id", $data["target_id"])
                    ->where("target_id", $data["source_id"]);
            })
            ->exists();

        if ($exists) {
            return response()->json(
                ["message" => "Такая связь уже существует"],
                422,
            );
        }

        $link = $topology->links()->create($data);

        return response()->json([
            "id" => $link->id,
            "source_id" => $link->source_id,
            "target_id" => $link->target_id,
            "label" => $link->label,
        ], 201);
    }

    public function destroyLink(NetworkDiagram $topology, NetworkLink $link)
    {
        $this->ensureBelongs($topology, $link->diagram_id);

        $link->delete();

        return response()->json(["status" => "ok"]);
    }

    // ------------------------------------------------------------------

    private function validateNode(Request $request, bool $partial = false): array
    {
        $required = $partial ? "sometimes|required" : "required";

        return $request->validate([
            "label" => "{$required}|string|max:255",
            "type" =>
                "{$required}|in:" .
                implode(",", array_keys(NetworkNode::TYPES)),
            "ip_address" => "nullable|string|max:45",
            "room_id" => "nullable|exists:rooms,id",
            "pos_x" => "nullable|integer",
            "pos_y" => "nullable|integer",
        ]);
    }

    private function nodePayload(NetworkNode $node): array
    {
        return [
            "id" => $node->id,
            "label" => $node->label,
            "type" => $node->type,
            "ip_address" => $node->ip_address,
            "room_id" => $node->room_id,
            "room_label" => $node->room
                ? trim($node->room->number . " " . ($node->room->name ?? ""))
                : null,
            "pos_x" => $node->pos_x,
            "pos_y" => $node->pos_y,
        ];
    }

    private function ensureBelongs(NetworkDiagram $topology, int $diagramId): void
    {
        if ($topology->id !== $diagramId) {
            abort(404);
        }
    }
}
