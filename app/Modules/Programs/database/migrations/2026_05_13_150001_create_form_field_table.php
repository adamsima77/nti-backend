<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_field', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_schema_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('name', 191);
            $table->string('type', 64);
            $table->json('config')->nullable();
            $table->timestamps();

            $table->foreign('form_schema_id')->references('id')->on('form_schema')->cascadeOnDelete();
            $table->unique(['form_schema_id', 'name']);
            $table->index(['form_schema_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_field');
    }
};
