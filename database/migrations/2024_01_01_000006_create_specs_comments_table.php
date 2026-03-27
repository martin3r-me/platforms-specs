<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specs_comments', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('document_id')->constrained('specs_documents')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('specs_sections')->cascadeOnDelete();
            $table->foreignId('requirement_id')->nullable()->constrained('specs_requirements')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('specs_comments')->nullOnDelete();
            $table->text('content');
            $table->timestamps();

            $table->index(['document_id', 'section_id'], 'specs_comments_doc_section_idx');
            $table->index(['document_id', 'requirement_id'], 'specs_comments_doc_req_idx');
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specs_comments');
    }
};
