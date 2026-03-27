<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specs_traces', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('source_requirement_id')->constrained('specs_requirements')->onDelete('cascade');
            $table->foreignId('target_requirement_id')->constrained('specs_requirements')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['source_requirement_id', 'target_requirement_id'], 'specs_traces_src_tgt_uq');
            $table->index('source_requirement_id', 'specs_traces_src_idx');
            $table->index('target_requirement_id', 'specs_traces_tgt_idx');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specs_traces');
    }
};
