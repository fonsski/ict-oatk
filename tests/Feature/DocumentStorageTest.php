<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Equipment;
use App\Models\EquipmentStatus;
use App\Models\User;
use App\Models\WriteOff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        Storage::fake('local');
        Storage::fake('public');
        EquipmentStatus::firstOrCreate(['slug' => 'working'], ['name' => 'Исправно']);
    }

    private function master(): User
    {
        return User::factory()->withRole('master')->create();
    }

    public function test_document_can_be_attached_to_equipment(): void
    {
        $equipment = Equipment::factory()->create();

        $this->actingAs($this->master())
            ->post(route('documents.store', ['equipment', $equipment->id]), [
                'file' => UploadedFile::fake()->create('akt.pdf', 300, 'application/pdf'),
                'type' => Document::TYPE_WRITE_OFF_ACT,
                'description' => 'Акт списания за июль',
            ])
            ->assertRedirect();

        $document = $equipment->documents()->first();
        $this->assertNotNull($document);
        $this->assertSame('akt.pdf', $document->original_name);
        $this->assertSame(Document::TYPE_WRITE_OFF_ACT, $document->type);
        $this->assertSame('Акт списания за июль', $document->description);
        Storage::disk('local')->assertExists($document->path);
    }

    public function test_uploaded_file_is_not_publicly_readable(): void
    {
        $equipment = Equipment::factory()->create();

        $this->actingAs($this->master())->post(
            route('documents.store', ['equipment', $equipment->id]),
            ['file' => UploadedFile::fake()->create('secret.pdf', 100, 'application/pdf')],
        );

        // Файл лежит на приватном диске, а не в public — прямой ссылки нет.
        $document = $equipment->documents()->first();
        Storage::disk('local')->assertExists($document->path);
        Storage::disk('public')->assertMissing($document->path);
    }

    public function test_guest_cannot_download_document(): void
    {
        $equipment = Equipment::factory()->create();
        $document = $equipment->documents()->create([
            'type' => Document::TYPE_OTHER,
            'path' => 'documents/equipment/1/x.pdf',
            'original_name' => 'x.pdf',
            'size' => 10,
        ]);

        $this->get(route('documents.download', $document))->assertUnauthorized();
    }

    public function test_staff_can_download_attached_document(): void
    {
        $equipment = Equipment::factory()->create();
        $this->actingAs($this->master())->post(
            route('documents.store', ['equipment', $equipment->id]),
            ['file' => UploadedFile::fake()->create('akt.pdf', 100, 'application/pdf')],
        );

        $this->actingAs($this->master())
            ->get(route('documents.download', $equipment->documents()->first()))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=akt.pdf');
    }

    public function test_disallowed_file_type_is_rejected(): void
    {
        $equipment = Equipment::factory()->create();

        $this->actingAs($this->master())
            ->post(route('documents.store', ['equipment', $equipment->id]), [
                'file' => UploadedFile::fake()->create('payload.exe', 50),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Document::count());
    }

    public function test_oversized_file_is_rejected(): void
    {
        $equipment = Equipment::factory()->create();

        $this->actingAs($this->master())
            ->post(route('documents.store', ['equipment', $equipment->id]), [
                'file' => UploadedFile::fake()->create('big.pdf', Document::MAX_SIZE_KB + 1, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Document::count());
    }

    public function test_unknown_subject_type_is_rejected(): void
    {
        $this->actingAs($this->master())
            ->post(route('documents.store', ['unknown-thing', 1]), [
                'file' => UploadedFile::fake()->create('akt.pdf', 10, 'application/pdf'),
            ])
            ->assertNotFound();
    }

    public function test_documents_attach_to_several_entity_types(): void
    {
        $equipment = Equipment::factory()->create();
        $writeOff = WriteOff::factory()->create();

        foreach ([['equipment', $equipment->id], ['write-off', $writeOff->id]] as [$slug, $id]) {
            $this->actingAs($this->master())->post(
                route('documents.store', [$slug, $id]),
                ['file' => UploadedFile::fake()->create('doc.pdf', 20, 'application/pdf')],
            );
        }

        $this->assertSame(1, $equipment->documents()->count());
        $this->assertSame(1, $writeOff->documents()->count());
        $this->assertSame(2, Document::count());
    }

    public function test_master_can_delete_document_and_its_file(): void
    {
        $equipment = Equipment::factory()->create();
        $this->actingAs($this->master())->post(
            route('documents.store', ['equipment', $equipment->id]),
            ['file' => UploadedFile::fake()->create('akt.pdf', 10, 'application/pdf')],
        );
        $document = $equipment->documents()->first();
        $path = $document->path;

        $this->actingAs($this->master())
            ->delete(route('documents.destroy', $document))
            ->assertRedirect();

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_technician_cannot_delete_document(): void
    {
        $equipment = Equipment::factory()->create();
        $document = $equipment->documents()->create([
            'type' => Document::TYPE_OTHER,
            'path' => 'documents/equipment/1/x.pdf',
            'original_name' => 'x.pdf',
            'size' => 10,
        ]);

        $this->actingAs(User::factory()->withRole('technician')->create())
            ->delete(route('documents.destroy', $document))
            ->assertForbidden();

        $this->assertDatabaseHas('documents', ['id' => $document->id]);
    }

    public function test_document_list_can_be_filtered_by_type(): void
    {
        $equipment = Equipment::factory()->create();
        $equipment->documents()->create([
            'type' => Document::TYPE_CONTRACT,
            'path' => 'a.pdf', 'original_name' => 'contract.pdf', 'size' => 1,
        ]);
        $equipment->documents()->create([
            'type' => Document::TYPE_INVOICE,
            'path' => 'b.pdf', 'original_name' => 'invoice.pdf', 'size' => 1,
        ]);

        $this->actingAs($this->master())
            ->get(route('documents.index', ['type' => Document::TYPE_CONTRACT]))
            ->assertOk()
            ->assertSee('contract.pdf')
            ->assertDontSee('invoice.pdf');
    }
}
