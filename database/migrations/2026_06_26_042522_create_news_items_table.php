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
        Schema::create('news_items', function (Blueprint $table) {
            $table->id();
            // The upstream entry id derived from the filename, e.g. "2026-06-04-1".
            $table->string('entry_id')->unique();
            $table->string('title');
            // Primary Atom <category> term + its human label (e.g. "releases" / "New PHP release").
            $table->string('category')->nullable();
            $table->string('label')->nullable();
            $table->json('terms');
            $table->longText('body_html');
            $table->string('link')->nullable();
            $table->string('via')->nullable();
            $table->timestamp('published_at')->index();
            // xxh128 of the source file — lets the importer skip unchanged entries.
            $table->string('source_hash', 32)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_items');
    }
};
