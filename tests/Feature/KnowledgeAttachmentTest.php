<?php

namespace Tests\Feature;

use App\Models\KnowledgeAttachment;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KnowledgeAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        Storage::fake('local');
    }

    private function staff(): User
    {
        return User::factory()->withRole('technician')->create();
    }

    private function article(): KnowledgeBase
    {
        $category = KnowledgeCategory::firstOrCreate(
            ['slug' => 'obshchie'],
            ['name' => 'Общие', 'is_active' => true, 'sort_order' => 0],
        );

        return KnowledgeBase::create([
            'title' => 'Статья с файлами',
            'slug' => 'statya-' . uniqid(),
            'category_id' => $category->id,
            'markdown' => 'x',
            'content' => '<p>x</p>',
            'status' => 'published',
            'author_id' => $this->staff()->id,
            'published_at' => now(),
        ]);
    }

    public function test_staff_can_attach_a_file(): void
    {
        $article = $this->article();

        $this->actingAs($this->staff())->post(
            route('knowledge.attachments.store', $article),
            ['file' => UploadedFile::fake()->create('manual.pdf', 200, 'application/pdf')],
        )->assertRedirect();

        $attachment = $article->attachments()->first();
        $this->assertNotNull($attachment);
        $this->assertSame('manual.pdf', $attachment->original_name);
        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_disallowed_file_type_is_rejected(): void
    {
        $article = $this->article();

        $this->actingAs($this->staff())->post(
            route('knowledge.attachments.store', $article),
            ['file' => UploadedFile::fake()->create('script.exe', 100)],
        )->assertSessionHasErrors('file');

        $this->assertSame(0, $article->attachments()->count());
    }

    public function test_attachment_can_be_downloaded_by_staff(): void
    {
        $article = $this->article();
        $this->actingAs($this->staff())->post(
            route('knowledge.attachments.store', $article),
            ['file' => UploadedFile::fake()->create('manual.pdf', 200, 'application/pdf')],
        );
        $attachment = $article->attachments()->first();

        $this->actingAs($this->staff())
            ->get(route('knowledge.attachments.download', [
                'knowledge' => $article, 'attachment' => $attachment,
            ]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=manual.pdf');
    }

    public function test_guest_cannot_download_attachment(): void
    {
        $article = $this->article();
        $attachment = $article->attachments()->create([
            'path' => 'knowledge/attachments/x.pdf',
            'original_name' => 'x.pdf', 'mime_type' => 'application/pdf', 'size' => 10,
        ]);

        $this->get(route('knowledge.attachments.download', [
            'knowledge' => $article, 'attachment' => $attachment,
        ]))->assertUnauthorized();
    }

    public function test_download_rejects_attachment_from_another_article(): void
    {
        $a = $this->article();
        $b = $this->article();
        $attachment = $b->attachments()->create([
            'path' => 'knowledge/attachments/y.pdf',
            'original_name' => 'y.pdf', 'mime_type' => 'application/pdf', 'size' => 10,
        ]);

        // Просим скачать вложение статьи B через URL статьи A.
        $this->actingAs($this->staff())
            ->get(route('knowledge.attachments.download', [
                'knowledge' => $a, 'attachment' => $attachment,
            ]))
            ->assertNotFound();
    }

    public function test_staff_can_delete_attachment(): void
    {
        $article = $this->article();
        $this->actingAs($this->staff())->post(
            route('knowledge.attachments.store', $article),
            ['file' => UploadedFile::fake()->create('manual.pdf', 200, 'application/pdf')],
        );
        $attachment = $article->attachments()->first();
        $path = $attachment->path;

        $this->actingAs($this->staff())->delete(route('knowledge.attachments.destroy', [
            'knowledge' => $article, 'attachment' => $attachment,
        ]))->assertRedirect();

        $this->assertDatabaseMissing('knowledge_attachments', ['id' => $attachment->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_force_deleting_article_removes_attachment_files(): void
    {
        $admin = User::factory()->withRole('admin')->create();
        $article = $this->article();
        $this->actingAs($this->staff())->post(
            route('knowledge.attachments.store', $article),
            ['file' => UploadedFile::fake()->create('manual.pdf', 200, 'application/pdf')],
        );
        $path = $article->attachments()->first()->path;
        $article->delete();

        $this->actingAs($admin)->delete(route('knowledge.force-delete', $article));

        $this->assertSame(0, KnowledgeAttachment::count());
        Storage::disk('local')->assertMissing($path);
    }
}
