<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_document', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('call_id');
            $table->unsignedBigInteger('document_id');
            $table->timestamps();

            $table->foreign('call_id')->references('id')->on('call')->onDelete('cascade');
            $table->foreign('document_id')->references('id')->on('document')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_document');
    }
};
