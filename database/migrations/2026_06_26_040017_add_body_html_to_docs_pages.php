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
            // Full DocBook body rendered to HTML — the complete, faithful page.
            $table->longText('body_html')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('docs_pages', function (Blueprint $table): void {
            $table->dropColumn('body_html');
        });
    }
};
