<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->boolean('is_flood_related')->nullable()->after('cleaned_at');
            $table->decimal('p_rel', 6, 4)->nullable()->after('is_flood_related');
            $table->decimal('d_clusters', 6, 4)->nullable()->after('p_rel');
            $table->decimal('confidence_score', 6, 4)->nullable()->after('d_clusters');
            $table->json('extracted_locations')->nullable()->after('confidence_score');
            $table->string('primary_barangay')->nullable()->index()->after('extracted_locations');
            $table->string('river_basin')->nullable()->after('primary_barangay');
            $table->decimal('latitude', 10, 7)->nullable()->after('river_basin');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->integer('cluster_id')->nullable()->after('longitude');
            $table->unsignedInteger('cluster_size')->nullable()->after('cluster_id');
            $table->string('model_status')->default('pending')->index()->after('cluster_size');
            $table->string('discard_reason')->nullable()->after('model_status');
            $table->timestamp('analyzed_at')->nullable()->after('discard_reason');
        });

        Schema::table('harvest_runs', function (Blueprint $table) {
            $table->unsignedInteger('posts_analyzed')->default(0)->after('cleaning_summary');
            $table->unsignedInteger('posts_actionable')->default(0)->after('posts_analyzed');
            $table->unsignedInteger('posts_early_warning')->default(0)->after('posts_actionable');
            $table->json('model_summary')->nullable()->after('posts_early_warning');
        });
    }

    public function down(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropColumn([
                'is_flood_related',
                'p_rel',
                'd_clusters',
                'confidence_score',
                'extracted_locations',
                'primary_barangay',
                'river_basin',
                'latitude',
                'longitude',
                'cluster_id',
                'cluster_size',
                'model_status',
                'discard_reason',
                'analyzed_at',
            ]);
        });

        Schema::table('harvest_runs', function (Blueprint $table) {
            $table->dropColumn([
                'posts_analyzed',
                'posts_actionable',
                'posts_early_warning',
                'model_summary',
            ]);
        });
    }
};

