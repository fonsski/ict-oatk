<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_event_participants', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('event_id')
                ->constrained('calendar_events')
                ->cascadeOnDelete();
            $table
                ->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Ответ на приглашение (RSVP).
            $table->string('response')->default('pending'); // pending | accepted | declined | maybe
            $table->timestamp('responded_at')->nullable();

            $table->timestamps();

            // Одного человека нельзя пригласить в событие дважды.
            $table->unique(['event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_event_participants');
    }
};
