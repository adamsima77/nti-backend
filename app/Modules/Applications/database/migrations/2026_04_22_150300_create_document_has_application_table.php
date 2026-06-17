<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_has_application', function (Blueprint $table) {
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('type_of_application_id');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->primary(['document_id', 'application_id', 'type_of_application_id']);

            $table->foreign('document_id')->references('id')->on('document');
            $table->foreign('application_id')->references('id')->on('application');
            $table->foreign('type_of_application_id')->references('id')->on('type_of_application');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_has_application');
    }
};
