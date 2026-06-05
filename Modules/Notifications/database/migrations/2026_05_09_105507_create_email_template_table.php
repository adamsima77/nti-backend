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
        Schema::create('email_template', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug', 60)->unique();
            $table->string('subject', 255);
            $table->longText('body_html');
            $table->unsignedBigInteger('notification_category_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('notification_category_id')
                ->references('id')
                ->on('notification_category');

            $table->enum('type', ['transactional', 'bulk'])
                ->default('transactional')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_template', function (Blueprint $table) {
            $table->dropForeign(['notification_category_id']);
        });

        Schema::dropIfExists('email_template');
    }
};
