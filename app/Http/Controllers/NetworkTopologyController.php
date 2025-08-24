<?php

namespace App\Http\Controllers;

use App\Models\NetworkTopology;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class NetworkTopologyController extends Controller
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
                !in_array(auth()->user()->role->slug, ['admin', 'technician'])
            ) {
                abort(403, 'Unauthorized action.');
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of network topologies
     */
    public function index()
    {
        $topologies = NetworkTopology::with('author')
            ->latest()
            ->paginate(10);

        return view('network-topology.index', compact('topologies'));
    }

    /**
     * Show the form for creating a new network topology
     */
    public function create()
    {
        return view('network-topology.create');
    }

    /**
     * Store a newly created network topology
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'data' => 'required|json',
        ]);

        $topology = new NetworkTopology();
        $topology->title = $data['title'];
        $topology->slug = Str::slug($data['title']);
        $topology->description = $data['description'] ?? null;
        $topology->data = $data['data'];
        $topology->author_id = Auth::id();
        $topology->save();

        return redirect()
            ->route('network-topology.show', $topology)
            ->with('success', 'Network topology created successfully');
    }

    /**
     * Display the specified network topology
     */
    public function show(NetworkTopology $networkTopology)
    {
        return view('network-topology.show', [
            'topology' => $networkTopology
        ]);
    }

    /**
     * Show the form for editing the network topology
     */
    public function edit(NetworkTopology $networkTopology)
    {
        // Check if user is allowed to edit this topology
        if (Auth::id() !== $networkTopology->author_id && !Auth::user()->isAdmin()) {
            abort(403, 'You are not authorized to edit this topology');
        }

        return view('network-topology.edit', [
            'topology' => $networkTopology
        ]);
    }

    /**
     * Update the specified network topology
     */
    public function update(Request $request, NetworkTopology $networkTopology)
    {
        // Check if user is allowed to edit this topology
        if (Auth::id() !== $networkTopology->author_id && !Auth::user()->isAdmin()) {
            abort(403, 'You are not authorized to edit this topology');
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'data' => 'required|json',
        ]);

        $networkTopology->title = $data['title'];
        $networkTopology->slug = Str::slug($data['title']);
        $networkTopology->description = $data['description'] ?? null;
        $networkTopology->data = $data['data'];
        $networkTopology->save();

        return redirect()
            ->route('network-topology.show', $networkTopology)
            ->with('success', 'Network topology updated successfully');
    }

    /**
     * Remove the specified network topology
     */
    public function destroy(NetworkTopology $networkTopology)
    {
        // Check if user is allowed to delete this topology
        if (Auth::id() !== $networkTopology->author_id && !Auth::user()->isAdmin()) {
            abort(403, 'You are not authorized to delete this topology');
        }

        $networkTopology->delete();

        return redirect()
            ->route('network-topology.index')
            ->with('success', 'Network topology deleted successfully');
    }
}
