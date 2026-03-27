<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specs_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('document_type', ['lastenheft', 'pflichtenheft']);
            $table->enum('status', ['backlog', 'in_progress', 'review', 'validated', 'archived'])->default('backlog');
            $table->string('public_token', 64)->nullable()->unique();
            $table->boolean('is_public')->default(false);
            $table->string('prefix', 10)->nullable();
            $table->integer('next_requirement_number')->default(1);
            $table->foreignId('linked_document_id')->nullable()->constrained('specs_documents')->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status'], 'specs_docs_team_status_idx');
            $table->index(['team_id', 'document_type'], 'specs_docs_team_type_idx');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specs_documents');
    }
};
