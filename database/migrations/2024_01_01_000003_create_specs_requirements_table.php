<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specs_requirements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('section_id')->constrained('specs_sections')->onDelete('cascade');
            $table->string('requirement_id', 20);
            $table->string('title');
            $table->text('content')->nullable();
            $table->enum('requirement_type', ['functional', 'non_functional', 'constraint', 'user_story', 'use_case'])->default('functional');
            $table->enum('priority', ['must', 'should', 'could', 'wont'])->default('should');
            $table->enum('status', ['draft', 'approved', 'implemented', 'verified'])->default('draft');
            $table->integer('position')->default(0);
            $table->json('metadata')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['section_id', 'position'], 'specs_reqs_section_pos_idx');
            $table->index(['section_id', 'requirement_type'], 'specs_reqs_section_type_idx');
            $table->index(['section_id', 'priority'], 'specs_reqs_section_prio_idx');
            $table->index(['section_id', 'status'], 'specs_reqs_section_status_idx');
            $table->index('uuid');
            $table->index('requirement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specs_requirements');
    }
};
