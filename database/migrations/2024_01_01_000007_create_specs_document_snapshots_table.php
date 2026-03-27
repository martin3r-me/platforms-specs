<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specs_document_snapshots', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('document_id')->constrained('specs_documents')->onDelete('cascade');
            $table->integer('version');
            $table->json('snapshot_data');
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['document_id', 'version'], 'specs_snapshots_doc_version_uq');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specs_document_snapshots');
    }
};
