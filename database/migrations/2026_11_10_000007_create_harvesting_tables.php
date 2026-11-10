<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harvest_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword')->unique();
            $table->string('platform', 50)->default('Facebook');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('matches_count')->default(0);
            $table->timestamp('last_scraped_at')->nullable();
            $table->timestamps();
        });

        Schema::create('verified_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('platform', 50)->default('Facebook');
            $table->date('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('harvest_runs', function (Blueprint $table) {
            $table->id();
            $table->string('status', 30)->default('running'); // running | completed | failed | stopped
            $table->json('queries_json')->nullable();
            $table->unsignedInteger('scrolls')->default(3);
            $table->boolean('headless')->default(false);
            $table->unsignedInteger('posts_collected')->default(0);
            $table->unsignedInteger('new_posts_saved')->default(0);
            $table->string('output_path')->nullable();
            $table->longText('log_excerpt')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('harvest_run_id')->nullable()->constrained('harvest_runs')->nullOnDelete();
            $table->string('post_id', 64)->unique();
            $table->string('search_query')->nullable()->index();
            $table->string('platform', 50)->default('Facebook');
            $table->string('author')->default('Unknown');
            $table->text('post_url')->nullable();
            $table->string('raw_timestamp')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->longText('full_text');
            $table->json('detected_locations')->nullable();
            $table->string('urgency_level', 30)->default('NORMAL');
            $table->json('media_urls')->nullable();
            $table->boolean('is_whitelisted_source')->default(false);
            $table->timestamp('scraped_at')->nullable();
            $table->timestamps();
        });

        // Seed initial default keyword listeners and verified source
        $now = now();
        DB::table('harvest_keywords')->insert([
            ['keyword' => '#bahaDavao', 'platform' => 'Facebook', 'is_active' => true, 'matches_count' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['keyword' => 'grabe baha davao', 'platform' => 'Facebook', 'is_active' => true, 'matches_count' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['keyword' => 'tulo ang tubig davao', 'platform' => 'Facebook', 'is_active' => true, 'matches_count' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('verified_sources')->insert([
            ['name' => 'Davao City DRRMO Page', 'platform' => 'Facebook', 'verified_at' => '2025-11-02', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
        Schema::dropIfExists('harvest_runs');
        Schema::dropIfExists('verified_sources');
        Schema::dropIfExists('harvest_keywords');
    }
};

