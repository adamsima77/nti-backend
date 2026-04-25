<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('organization', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255);
            $table->string('phone', 30);
            $table->string('ico', 30);
            $table->string('web_url', 255);
            $table->unsignedBigInteger('address_id');
            $table->timestamps();

            $table->foreign('address_id')
                ->references('id')
                ->on('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization', function (Blueprint $table) {
            $table->dropForeign(['address_id']);
        });

        Schema::dropIfExists('organization');
    }
};
