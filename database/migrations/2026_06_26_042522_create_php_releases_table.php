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
        Schema::create('php_releases', function (Blueprint $table) {
            $table->id();
            // One row per active branch (e.g. "8.5"); holds that branch's current release.
            $table->string('branch')->unique();
            $table->string('version');
            $table->date('released_on')->nullable();
            // Upstream release tags, e.g. ["security"].
            $table->json('tags');
            // Map of archive format => sha256, e.g. {"tar.gz": "…", "tar.xz": "…"}.
            $table->json('sha256');
            $table->string('source_hash', 32)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('php_releases');
    }
};
