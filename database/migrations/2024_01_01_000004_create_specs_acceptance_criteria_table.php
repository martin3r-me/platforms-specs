<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specs_acceptance_criteria', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('requirement_id')->constrained('specs_requirements')->onDelete('cascade');
            $table->text('content');
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index(['requirement_id', 'position'], 'specs_ac_req_pos_idx');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specs_acceptance_criteria');
    }
};
