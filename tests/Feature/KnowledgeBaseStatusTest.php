<?php

namespace Tests\Feature;

use App\Models\KnowledgeBase;
use App\Models\KnowledgeCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KnowledgeBaseStatusTest extends TestCase
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
        return KnowledgeCategory::firstOrCreate(
            ['slug' => 'obshchie'],
            ['name' => 'Общие', 'is_active' => true, 'sort_order' => 0],
        );
    }

    private function article(User $author, string $status, array $attrs = []): KnowledgeBase
    {
        return KnowledgeBase::create(array_merge([
            'title' => 'Статья ' . uniqid(),
            'slug' => 'st-' . uniqid(),
            'category_id' => $this->category()->id,
            'markdown' => 'x',
            'content' => '<p>x</p>',
            'status' => $status,
            'author_id' => $author->id,
            'published_at' => $status === KnowledgeBase::STATUS_PUBLISHED ? now() : null,
        ], $attrs));
    }

    private function payload(int $categoryId, string $action): array
    {
        return [
            'title' => 'Черновик про сеть',
            'category_id' => $categoryId,
            'content' => 'Содержимое статьи достаточной длины для валидации.',
            'action' => $action,
        ];
    }

    public function test_saving_as_draft_sets_draft_status(): void
    {
        $author = $this->user('technician');
        $category = $this->category();

        $this->actingAs($author)->post(route('knowledge.store'), $this->payload($category->id, 'draft'));

        $article = KnowledgeBase::first();
        $this->assertTrue($article->isDraft());
        $this->assertNull($article->published_at);
    }

    public function test_publishing_on_create_sets_published_status(): void
    {
        $author = $this->user('technician');
        $category = $this->category();

        $this->actingAs($author)->post(route('knowledge.store'), $this->payload($category->id, 'publish'));

        $article = KnowledgeBase::first();
        $this->assertTrue($article->isPublished());
        $this->assertNotNull($article->published_at);
    }

    public function test_draft_is_hidden_from_index(): void
    {
        $author = $this->user('technician');
        $this->article($author, KnowledgeBase::STATUS_DRAFT, ['title' => 'Секретный черновик']);

        $this->actingAs($this->user('technician'))
            ->get(route('knowledge.index'))
            ->assertOk()
            ->assertDontSee('Секретный черновик', false);
    }

    public function test_author_can_view_own_draft_but_others_cannot(): void
    {
        $author = $this->user('technician');
        $draft = $this->article($author, KnowledgeBase::STATUS_DRAFT);

        $this->actingAs($author)->get(route('knowledge.show', $draft))->assertOk();
        $this->actingAs($this->user('technician'))->get(route('knowledge.show', $draft))->assertNotFound();
        $this->actingAs($this->user('admin'))->get(route('knowledge.show', $draft))->assertOk();
    }

    public function test_drafts_view_shows_only_own_drafts(): void
    {
        $me = $this->user('technician');
        $other = $this->user('technician');

        $this->article($me, KnowledgeBase::STATUS_DRAFT, ['title' => 'Мой черновик']);
        $this->article($other, KnowledgeBase::STATUS_DRAFT, ['title' => 'Чужой черновик']);

        $this->actingAs($me)
            ->get(route('knowledge.drafts'))
            ->assertOk()
            ->assertSee('Мой черновик', false)
            ->assertDontSee('Чужой черновик', false);
    }

    public function test_author_can_publish_draft(): void
    {
        $author = $this->user('technician');
        $draft = $this->article($author, KnowledgeBase::STATUS_DRAFT);

        $this->actingAs($author)->post(route('knowledge.publish', $draft));

        $this->assertTrue($draft->fresh()->isPublished());
        $this->assertNotNull($draft->fresh()->published_at);
    }

    public function test_publishing_others_draft_is_forbidden_for_non_manager(): void
    {
        $author = $this->user('technician');
        $draft = $this->article($author, KnowledgeBase::STATUS_DRAFT);

        $this->actingAs($this->user('technician'))
            ->post(route('knowledge.publish', $draft))
            ->assertForbidden();
    }

    public function test_archiving_published_article(): void
    {
        $author = $this->user('technician');
        $article = $this->article($author, KnowledgeBase::STATUS_PUBLISHED, ['title' => 'В архив меня']);

        $this->actingAs($this->user('admin'))->post(route('knowledge.archive-article', $article));

        $this->assertTrue($article->fresh()->isArchived());

        // Больше не в общем списке.
        $this->actingAs($this->user('technician'))
            ->get(route('knowledge.index'))
            ->assertDontSee('В архив меня', false);
    }

    public function test_archive_view_access_and_restore(): void
    {
        $author = $this->user('technician');
        $article = $this->article($author, KnowledgeBase::STATUS_ARCHIVED, ['title' => 'Архивная статья']);

        // Техник не может открыть архив.
        $this->actingAs($this->user('technician'))->get(route('knowledge.archive'))->assertForbidden();

        // Мастер видит архив.
        $this->actingAs($this->user('master'))
            ->get(route('knowledge.archive'))
            ->assertOk()
            ->assertSee('Архивная статья', false);

        // Возврат из архива публикует статью.
        $this->actingAs($this->user('master'))->post(route('knowledge.publish', $article));
        $this->assertTrue($article->fresh()->isPublished());
    }
}
