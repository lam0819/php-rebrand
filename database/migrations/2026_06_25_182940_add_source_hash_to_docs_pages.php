<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('docs_pages', function (Blueprint $table): void {
            // Content hash of the source XML; lets re-imports skip unchanged files.
            $table->string('source_hash', 64)->nullable()->after('source_path');
        });
    }

    public function down(): void
    {
        Schema::table('docs_pages', function (Blueprint $table): void {
            $table->dropColumn('source_hash');
        });
    }
};
