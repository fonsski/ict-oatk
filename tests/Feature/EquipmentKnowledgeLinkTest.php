<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentStatus;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EquipmentKnowledgeLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        EquipmentStatus::firstOrCreate(['slug' => 'working'], ['name' => 'Исправно']);
    }

    private function staff(): User
    {
        return User::factory()->withRole('technician')->create();
    }

    private function article(string $title = 'Инструкция по замене картриджа'): KnowledgeBase
    {
        $category = KnowledgeCategory::firstOrCreate(
            ['slug' => 'obshchie'],
            ['name' => 'Общие', 'is_active' => true, 'sort_order' => 0],
        );

        return KnowledgeBase::create([
            'title' => $title,
            'slug' => 'article-' . uniqid(),
            'category_id' => $category->id,
            'markdown' => 'x',
            'content' => '<p>x</p>',
            'status' => KnowledgeBase::STATUS_PUBLISHED,
            'author_id' => $this->staff()->id,
            'published_at' => now(),
        ]);
    }

    public function test_staff_can_link_article_to_equipment(): void
    {
        $equipment = Equipment::factory()->create();
        $article = $this->article();

        $this->actingAs($this->staff())
            ->post(route('equipment.knowledge.store', $equipment), [
                'knowledge_base_id' => $article->id,
            ])
            ->assertRedirect();

        $this->assertTrue($equipment->knowledgeArticles()->where('knowledge_bases.id', $article->id)->exists());
    }

    public function test_link_is_visible_from_both_sides(): void
    {
        $equipment = Equipment::factory()->create();
        $article = $this->article();
        $equipment->knowledgeArticles()->attach($article->id);

        $this->assertSame(1, $equipment->knowledgeArticles()->count());
        $this->assertSame(1, $article->equipment()->count());
        $this->assertSame($equipment->id, $article->equipment()->first()->id);
    }

    public function test_linking_same_article_twice_does_not_duplicate(): void
    {
        $equipment = Equipment::factory()->create();
        $article = $this->article();

        foreach (range(1, 2) as $ignored) {
            $this->actingAs($this->staff())->post(
                route('equipment.knowledge.store', $equipment),
                ['knowledge_base_id' => $article->id],
            );
        }

        $this->assertSame(1, $equipment->knowledgeArticles()->count());
    }

    public function test_staff_can_unlink_article(): void
    {
        $equipment = Equipment::factory()->create();
        $article = $this->article();
        $equipment->knowledgeArticles()->attach($article->id);

        $this->actingAs($this->staff())
            ->delete(route('equipment.knowledge.destroy', [$equipment, $article]))
            ->assertRedirect();

        $this->assertSame(0, $equipment->knowledgeArticles()->count());
    }

    public function test_search_excludes_already_linked_articles(): void
    {
        $equipment = Equipment::factory()->create();
        $linked = $this->article('Уже привязанная статья');
        $free = $this->article('Свободная статья');
        $equipment->knowledgeArticles()->attach($linked->id);

        $response = $this->actingAs($this->staff())
            ->getJson(route('equipment.knowledge.search', $equipment))
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($free->id));
        $this->assertFalse($ids->contains($linked->id));
    }

    public function test_equipment_card_shows_linked_articles(): void
    {
        $equipment = Equipment::factory()->create();
        $article = $this->article('Регламент обслуживания принтера');
        $equipment->knowledgeArticles()->attach($article->id);

        $this->actingAs($this->staff())
            ->get(route('equipment.show', $equipment))
            ->assertOk()
            ->assertSee('Регламент обслуживания принтера');
    }

    public function test_deleting_equipment_removes_the_pivot_row(): void
    {
        $equipment = Equipment::factory()->create();
        $article = $this->article();
        $equipment->knowledgeArticles()->attach($article->id);

        $equipment->forceDelete();

        $this->assertSame(0, $article->equipment()->count());
        $this->assertDatabaseCount('equipment_knowledge_base', 0);
    }
}
