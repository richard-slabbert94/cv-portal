<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cv_versions', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('content');
        });

        Schema::table('access_codes', function (Blueprint $table) {
            // Lets a cover letter be hidden without losing the text.
            $table->boolean('show_cover_letter')->default(true)->after('cover_letter');
        });
    }

    public function down(): void
    {
        Schema::table('access_codes', function (Blueprint $table) {
            $table->dropColumn('show_cover_letter');
        });

        Schema::table('cv_versions', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};
