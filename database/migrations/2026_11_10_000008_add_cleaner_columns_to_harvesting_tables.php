<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->longText('cleaned_text')->nullable()->after('full_text');
            $table->string('clean_status', 30)->default('pending')->index()->after('cleaned_text'); // pending | kept | removed
            $table->string('removal_category', 100)->nullable()->index()->after('clean_status');
            $table->string('removal_reason')->nullable()->after('removal_category');
            $table->timestamp('cleaned_at')->nullable()->after('scraped_at');
        });

        Schema::table('harvest_runs', function (Blueprint $table) {
            $table->unsignedInteger('posts_kept')->default(0)->after('new_posts_saved');
            $table->unsignedInteger('posts_removed')->default(0)->after('posts_kept');
            $table->json('cleaning_summary')->nullable()->after('posts_removed');
        });
    }

    public function down(): void
    {
        Schema::table('harvest_runs', function (Blueprint $table) {
            $table->dropColumn(['posts_kept', 'posts_removed', 'cleaning_summary']);
        });

        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropColumn(['cleaned_text', 'clean_status', 'removal_category', 'removal_reason', 'cleaned_at']);
        });
    }
};

