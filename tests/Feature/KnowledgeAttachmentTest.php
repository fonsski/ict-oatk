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

    /**
     * Архивы принимаются по началу файла: zip, rar и 7z. Раньше проверка
     * шла по определению типа, и обычный zip отклонялся ошибкой
     * «validation.mimes».
     */
    public static function archiveProvider(): array
    {
        return [
            'zip' => ['docs.zip', "PK\x03\x04" . str_repeat("\0", 64)],
            'zip без файлов' => ['empty.zip', "PK\x05\x06" . str_repeat("\0", 18)],
            'rar' => ['docs.rar', "Rar!\x1A\x07\x01\x00" . str_repeat("\0", 64)],
            'rar старого формата' => ['old.rar', "Rar!\x1A\x07\x00" . str_repeat("\0", 64)],
            '7z' => ['docs.7z', "7z\xBC\xAF\x27\x1C" . str_repeat("\0", 64)],
            'заглавное расширение' => ['DOCS.ZIP', "PK\x03\x04" . str_repeat("\0", 64)],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('archiveProvider')]
    public function test_staff_can_attach_an_archive(string $name, string $bytes): void
    {
        $article = $this->article();

        $this->actingAs($this->staff())->post(
            route('knowledge.attachments.store', $article),
            ['file' => UploadedFile::fake()->createWithContent($name, $bytes)],
        )->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame($name, $article->attachments()->first()->original_name);
    }

    public function test_text_renamed_to_archive_is_rejected_with_a_readable_message(): void
    {
        $article = $this->article();

        $response = $this->actingAs($this->staff())->post(
            route('knowledge.attachments.store', $article),
            ['file' => UploadedFile::fake()->createWithContent('fake.zip', 'это просто текст')],
        );

        $response->assertSessionHasErrors('file');
        $this->assertStringContainsString('не похож на архив', session('errors')->first('file'));
        $this->assertSame(0, $article->attachments()->count());
    }

    public function test_unsupported_type_error_is_not_a_raw_translation_key(): void
    {
        $article = $this->article();

        $this->actingAs($this->staff())->post(
            route('knowledge.attachments.store', $article),
            ['file' => UploadedFile::fake()->create('script.exe', 100)],
        );

        $message = session('errors')->first('file');
        $this->assertStringNotContainsString('validation.', $message);
        $this->assertStringContainsString('rar', $message);
    }

    public function test_russian_validation_messages_are_available(): void
    {
        $this->assertNotSame('validation.mimes', __('validation.mimes'));
        $this->assertNotSame('validation.required', __('validation.required'));
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
