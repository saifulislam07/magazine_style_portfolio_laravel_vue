<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('kind', 24);
            $table->string('label')->nullable();
            $table->unsignedSmallInteger('position')->index();
            $table->json('content');
            $table->timestamps();
        });

        Schema::create('book_settings', function (Blueprint $table) {
            $table->id();
            $table->json('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_settings');
        Schema::dropIfExists('book_pages');
    }
};
