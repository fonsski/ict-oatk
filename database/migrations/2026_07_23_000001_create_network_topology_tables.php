<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_diagrams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table
                ->foreignId('author_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('network_nodes', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('diagram_id')
                ->constrained('network_diagrams')
                ->cascadeOnDelete();
            $table->string('label');
            $table->string('type')->default('other');
            $table->string('ip_address')->nullable();
            $table
                ->foreignId('room_id')
                ->nullable()
                ->constrained('rooms')
                ->nullOnDelete();
            $table->integer('pos_x')->default(80);
            $table->integer('pos_y')->default(80);
            $table->timestamps();
        });

        Schema::create('network_links', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('diagram_id')
                ->constrained('network_diagrams')
                ->cascadeOnDelete();
            $table
                ->foreignId('source_id')
                ->constrained('network_nodes')
                ->cascadeOnDelete();
            $table
                ->foreignId('target_id')
                ->constrained('network_nodes')
                ->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_links');
        Schema::dropIfExists('network_nodes');
        Schema::dropIfExists('network_diagrams');
    }
};
