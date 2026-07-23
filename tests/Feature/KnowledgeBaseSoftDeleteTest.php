<?php

namespace Tests\Feature;

use App\Models\KnowledgeBase;
use App\Models\KnowledgeCategory;
use App\Models\KnowledgeImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KnowledgeBaseSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    private function user(string $role): User
    {
        return User::factory()->withRole($role)->create();
    }

    private function category(): KnowledgeCategory
    {
        return KnowledgeCategory::create([
            'name' => 'Общие',
            'slug' => 'obshchie',
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    private function article(array $attrs = []): KnowledgeBase
    {
        return KnowledgeBase::create(array_merge([
            'title' => 'Настройка почты',
            'slug' => 'nastroyka-pochty',
            'category_id' => $this->category()->id,
            'markdown' => 'Текст инструкции.',
            'content' => '<p>Текст инструкции.</p>',
            'author_id' => $this->user('technician')->id,
            'published_at' => now(),
            'views_count' => 0,
        ], $attrs));
    }

    public function test_destroy_soft_deletes_article(): void
    {
        $article = $this->article();

        $this->actingAs($this->user('technician'))
            ->delete(route('knowledge.destroy', $article))
            ->assertRedirect(route('knowledge.index'));

        $this->assertSoftDeleted('knowledge_bases', ['id' => $article->id]);
        $this->assertSame(0, KnowledgeBase::count());
        $this->assertSame(1, KnowledgeBase::withTrashed()->count());
    }

    public function test_show_resolves_by_slug(): void
    {
        $article = $this->article(['slug' => 'unikalnyy-slag']);

        $this->actingAs($this->user('technician'))
            ->get('/knowledge/unikalnyy-slag')
            ->assertOk()
            ->assertSee('Настройка почты', false);
    }

    public function test_soft_deleted_article_is_not_viewable(): void
    {
        $article = $this->article();
        $article->delete();

        $this->actingAs($this->user('admin'))
            ->get('/knowledge/' . $article->slug)
            ->assertNotFound();
    }

    public function test_admin_and_master_can_open_trash_but_technician_cannot(): void
    {
        $this->article();

        $this->actingAs($this->user('admin'))->get(route('knowledge.trashed'))->assertOk();
        $this->actingAs($this->user('master'))->get(route('knowledge.trashed'))->assertOk();
        $this->actingAs($this->user('technician'))->get(route('knowledge.trashed'))->assertForbidden();
    }

    public function test_admin_can_restore_article(): void
    {
        $article = $this->article();
        $article->delete();

        $this->actingAs($this->user('admin'))
            ->post(route('knowledge.restore', $article));

        $this->assertNotSoftDeleted('knowledge_bases', ['id' => $article->id]);
    }

    public function test_technician_cannot_restore_article(): void
    {
        $article = $this->article();
        $article->delete();

        $this->actingAs($this->user('technician'))
            ->post(route('knowledge.restore', $article))
            ->assertForbidden();

        $this->assertSoftDeleted('knowledge_bases', ['id' => $article->id]);
    }

    public function test_admin_force_delete_removes_article_and_images(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('a.png')->storeAs('knowledge/images', 'a.png', 'public');

        $article = $this->article();
        KnowledgeImage::create([
            'knowledge_base_id' => $article->id,
            'path' => $path,
            'alt' => 'a.png',
        ]);
        $article->delete();

        $this->actingAs($this->user('admin'))
            ->delete(route('knowledge.force-delete', $article));

        $this->assertDatabaseMissing('knowledge_bases', ['id' => $article->id]);
        $this->assertDatabaseMissing('knowledge_images', ['knowledge_base_id' => $article->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_master_cannot_force_delete(): void
    {
        $article = $this->article();
        $article->delete();

        $this->actingAs($this->user('master'))
            ->delete(route('knowledge.force-delete', $article))
            ->assertForbidden();

        $this->assertSoftDeleted('knowledge_bases', ['id' => $article->id]);
    }

    public function test_new_article_gets_distinct_slug_from_trashed_one(): void
    {
        $category = $this->category();
        $trashed = KnowledgeBase::create([
            'title' => 'Повтор',
            'slug' => 'povtor',
            'category_id' => $category->id,
            'markdown' => 'x',
            'content' => '<p>x</p>',
            'author_id' => $this->user('technician')->id,
            'published_at' => now(),
        ]);
        $trashed->delete();

        $this->actingAs($this->user('technician'))->post(route('knowledge.store'), [
            'title' => 'Повтор',
            'category_id' => $category->id,
            'content' => 'Достаточно длинное содержимое для прохождения валидации.',
        ]);

        $fresh = KnowledgeBase::where('title', 'Повтор')->first();
        $this->assertNotSame('povtor', $fresh->slug);
    }
}
