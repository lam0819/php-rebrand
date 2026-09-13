<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docs_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('doc_id')->unique();      // stable id derived from source path
            $table->string('slug')->index();         // URL-friendly identifier
            $table->string('title');
            $table->string('type')->index();         // DocumentType value (refentry, chapter, …)
            $table->string('source_path');           // path within the source repository
            $table->longText('raw_xml')->nullable(); // archival copy of the source XML
            $table->longText('content')->nullable(); // normalized, render-ready body
            $table->json('metadata')->nullable();    // sections, params, examples, see-also, …
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docs_pages');
    }
};
