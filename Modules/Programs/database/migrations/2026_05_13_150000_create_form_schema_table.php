<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_schema', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('call_id');
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 32)->default('draft');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->json('sections')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->foreign('call_id')->references('id')->on('call')->cascadeOnDelete();
            $table->index(['call_id', 'status', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_schema');
    }
};
