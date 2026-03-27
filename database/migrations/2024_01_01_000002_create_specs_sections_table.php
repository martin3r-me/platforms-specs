<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specs_sections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('document_id')->constrained('specs_documents')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('specs_sections')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index(['document_id', 'position'], 'specs_sections_doc_pos_idx');
            $table->index(['document_id', 'parent_id'], 'specs_sections_doc_parent_idx');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specs_sections');
    }
};
