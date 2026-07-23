<?php

namespace Tests\Feature;

use App\Models\KnowledgeBase;
use App\Models\KnowledgeCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KnowledgeBaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    private function staff(): User
    {
        return User::factory()->withRole('technician')->create();
    }

    private function category(string $name = 'Общие'): KnowledgeCategory
    {
        return KnowledgeCategory::create([
            'name' => $name,
            'slug' => \Str::slug($name),
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    private function article(KnowledgeCategory $category, array $attrs = []): KnowledgeBase
    {
        return KnowledgeBase::create(array_merge([
            'title' => 'Как настроить принтер',
            'slug' => 'kak-nastroit-printer',
            'category_id' => $category->id,
            'markdown' => 'Инструкция по настройке.',
            'content' => '<p>Инструкция по настройке.</p>',
            'author_id' => $this->staff()->id,
            'published_at' => now(),
            'views_count' => 0,
        ], $attrs));
    }

    public function test_guest_cannot_access_knowledge_base(): void
    {
        $this->get(route('knowledge.index'))->assertUnauthorized();
    }

    public function test_staff_can_open_knowledge_index(): void
    {
        $this->category();

        $this->actingAs($this->staff())
            ->get(route('knowledge.index'))
            ->assertOk();
    }

    public function test_search_respects_category_filter(): void
    {
        $catA = $this->category('Категория А');
        $catB = $this->category('Категория Б');

        $this->article($catA, [
            'title' => 'Настройка Wi-Fi',
            'slug' => 'wifi-a',
            'content' => '<p>Просто текст</p>',
        ]);
        $needleInB = $this->article($catB, [
            'title' => 'Замена картриджа',
            'slug' => 'kartridzh-b',
            'content' => '<p>уникальноеслово в контенте</p>',
        ]);

        // Ищем слово, которое есть только в статье категории Б,
        // но фильтруем по категории А — статья Б не должна попасть в выдачу.
        $response = $this->actingAs($this->staff())->get(
            route('knowledge.index', [
                'category' => $catA->id,
                'search' => 'уникальноеслово',
            ]),
        );

        $response->assertOk()->assertDontSee('Замена картриджа', false);
    }

    public function test_viewing_article_increments_view_count(): void
    {
        $article = $this->article($this->category());

        $this->actingAs($this->staff())
            ->get(route('knowledge.show', $article))
            ->assertOk();

        $this->assertSame(1, $article->fresh()->views_count);
    }

    public function test_staff_can_create_article_with_unique_slug(): void
    {
        $category = $this->category();
        $this->article($category, ['title' => 'Дубликат', 'slug' => 'dublikat']);

        $this->actingAs($this->staff())->post(route('knowledge.store'), [
            'title' => 'Дубликат',
            'category_id' => $category->id,
            'content' => 'Достаточно длинное содержимое статьи для валидации.',
        ]);

        $slugs = KnowledgeBase::where('title', 'Дубликат')->pluck('slug');
        $this->assertCount(2, $slugs);
        $this->assertSame($slugs->count(), $slugs->unique()->count());
    }

    public function test_image_upload_returns_json_and_stores_file(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->staff())->post(
            route('knowledge.upload-image'),
            ['image' => UploadedFile::fake()->image('scheme.png', 640, 480)],
        );

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'url', 'markdown']);

        $files = Storage::disk('public')->files('knowledge/images');
        $this->assertCount(1, $files);
    }

    public function test_image_upload_rejects_non_image_with_json_error(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->staff())->post(
            route('knowledge.upload-image'),
            ['image' => UploadedFile::fake()->create('virus.exe', 100)],
        );

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_deleting_category_with_articles_is_blocked(): void
    {
        $category = $this->category();
        $this->article($category);

        $this->actingAs($this->staff())
            ->delete(route('knowledge.categories.destroy', $category))
            ->assertRedirect(route('knowledge.categories.index'));

        $this->assertDatabaseHas('knowledge_categories', ['id' => $category->id]);
    }
}
