<?php

namespace Tests\Feature;

use App\Models\NetworkDiagram;
use App\Models\NetworkLink;
use App\Models\NetworkNode;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetworkTopologyTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role = 'technician'): User
    {
        return User::factory()->withRole($role)->create();
    }

    private function diagram(?User $author = null): NetworkDiagram
    {
        return NetworkDiagram::create([
            'name' => 'Главный корпус',
            'author_id' => ($author ?? $this->staff())->id,
        ]);
    }

    public function test_guest_cannot_access_topology(): void
    {
        $this->get(route('topology.index'))->assertUnauthorized();
    }

    public function test_technician_can_create_diagram(): void
    {
        $this->actingAs($this->staff('technician'))
            ->post(route('topology.store'), ['name' => 'Лаборатории'])
            ->assertRedirect();

        $this->assertDatabaseHas('network_diagrams', ['name' => 'Лаборатории']);
    }

    public function test_editor_page_renders(): void
    {
        $diagram = $this->diagram();

        $this->actingAs($this->staff())
            ->get(route('topology.show', $diagram))
            ->assertOk()
            ->assertSee('topology-canvas', false);
    }

    public function test_add_node_via_api(): void
    {
        $diagram = $this->diagram();

        $response = $this->actingAs($this->staff())->postJson(
            route('topology.nodes.store', $diagram),
            ['label' => 'Ядро', 'type' => 'switch', 'pos_x' => 100, 'pos_y' => 120],
        );

        $response->assertStatus(201)->assertJsonPath('label', 'Ядро');
        $this->assertDatabaseHas('network_nodes', [
            'diagram_id' => $diagram->id,
            'label' => 'Ядро',
            'type' => 'switch',
        ]);
    }

    public function test_node_type_is_validated(): void
    {
        $diagram = $this->diagram();

        $this->actingAs($this->staff())
            ->postJson(route('topology.nodes.store', $diagram), [
                'label' => 'X',
                'type' => 'nonsense',
            ])
            ->assertStatus(422);
    }

    public function test_node_can_bind_to_room(): void
    {
        $diagram = $this->diagram();
        $room = Room::create([
            'number' => '101',
            'name' => 'Класс',
            'type' => 'classroom',
            'is_active' => true,
        ]);

        $this->actingAs($this->staff())->postJson(
            route('topology.nodes.store', $diagram),
            ['label' => 'ПК', 'type' => 'workstation', 'room_id' => $room->id],
        )->assertStatus(201)->assertJsonPath('room_id', $room->id);
    }

    public function test_update_node_position(): void
    {
        $diagram = $this->diagram();
        $node = $diagram->nodes()->create([
            'label' => 'A', 'type' => 'router', 'pos_x' => 0, 'pos_y' => 0,
        ]);

        $this->actingAs($this->staff())->putJson(
            route('topology.nodes.update', ['topology' => $diagram, 'node' => $node]),
            ['pos_x' => 300, 'pos_y' => 250],
        )->assertOk();

        $this->assertDatabaseHas('network_nodes', [
            'id' => $node->id, 'pos_x' => 300, 'pos_y' => 250,
        ]);
    }

    public function test_create_and_prevent_duplicate_link(): void
    {
        $diagram = $this->diagram();
        $a = $diagram->nodes()->create(['label' => 'A', 'type' => 'router']);
        $b = $diagram->nodes()->create(['label' => 'B', 'type' => 'switch']);

        $this->actingAs($this->staff())->postJson(
            route('topology.links.store', $diagram),
            ['source_id' => $a->id, 'target_id' => $b->id],
        )->assertStatus(201);

        // Обратное направление — дубликат, отклоняется.
        $this->actingAs($this->staff())->postJson(
            route('topology.links.store', $diagram),
            ['source_id' => $b->id, 'target_id' => $a->id],
        )->assertStatus(422);

        $this->assertSame(1, NetworkLink::count());
    }

    public function test_link_rejects_node_from_other_diagram(): void
    {
        $diagram = $this->diagram();
        $other = $this->diagram();
        $a = $diagram->nodes()->create(['label' => 'A', 'type' => 'router']);
        $foreign = $other->nodes()->create(['label' => 'B', 'type' => 'switch']);

        $this->actingAs($this->staff())->postJson(
            route('topology.links.store', $diagram),
            ['source_id' => $a->id, 'target_id' => $foreign->id],
        )->assertStatus(422);
    }

    public function test_deleting_node_cascades_links(): void
    {
        $diagram = $this->diagram();
        $a = $diagram->nodes()->create(['label' => 'A', 'type' => 'router']);
        $b = $diagram->nodes()->create(['label' => 'B', 'type' => 'switch']);
        $diagram->links()->create(['source_id' => $a->id, 'target_id' => $b->id]);

        $this->actingAs($this->staff())->deleteJson(
            route('topology.nodes.destroy', ['topology' => $diagram, 'node' => $a]),
        )->assertOk();

        $this->assertDatabaseMissing('network_nodes', ['id' => $a->id]);
        $this->assertSame(0, NetworkLink::count());
    }

    public function test_print_view_renders_nodes(): void
    {
        $diagram = $this->diagram();
        $diagram->nodes()->create(['label' => 'Ядро сети', 'type' => 'switch']);

        $this->actingAs($this->staff())
            ->get(route('topology.print', $diagram))
            ->assertOk()
            ->assertSee('Ядро сети', false)
            ->assertSee('window.print()', false);
    }

    public function test_deleting_diagram_removes_nodes_and_links(): void
    {
        $diagram = $this->diagram();
        $a = $diagram->nodes()->create(['label' => 'A', 'type' => 'router']);
        $b = $diagram->nodes()->create(['label' => 'B', 'type' => 'switch']);
        $diagram->links()->create(['source_id' => $a->id, 'target_id' => $b->id]);

        $this->actingAs($this->staff('admin'))
            ->delete(route('topology.destroy', $diagram))
            ->assertRedirect(route('topology.index'));

        $this->assertDatabaseMissing('network_diagrams', ['id' => $diagram->id]);
        $this->assertSame(0, NetworkNode::count());
        $this->assertSame(0, NetworkLink::count());
    }
}
