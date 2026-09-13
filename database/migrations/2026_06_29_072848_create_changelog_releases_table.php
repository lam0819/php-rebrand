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
        Schema::create('changelog_releases', function (Blueprint $table) {
            $table->id();
            // e.g. "8.5.7" — unique across branches.
            $table->string('version')->unique();
            $table->string('branch')->index();
            $table->date('released_on')->nullable();
            // false for an in-development entry headed "?? ??? ????".
            $table->boolean('released')->default(true);
            // Categorised entries: [{category: "Core", entries: ["…", …]}, …].
            $table->json('sections');
            $table->unsignedInteger('entry_count')->default(0);
            $table->string('source_hash', 32)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('changelog_releases');
    }
};
