<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Javaabu\Cms\Upgrade\V1Tables;

/**
 * Upgrading from javaabu/cms v1: v1's `posts` table is in the way of
 * javaabu/post-types' (whose migration runs after this one). When a v1
 * `posts` table is found, v1's tables are renamed to cms_v1_{table}, kept for
 * `php artisan cms:import-v1`. New installs aren't touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! config('cms.upgrade.rename_v1_tables', true)) {
            return;
        }

        V1Tables::rename();
    }

    public function down(): void
    {
        if (Schema::hasTable('posts') || ! V1Tables::hasRenamed()) {
            return;
        }

        foreach (array_reverse(V1Tables::TABLES) as $table) {
            if (Schema::hasTable(V1Tables::renamed($table)) && ! Schema::hasTable($table)) {
                Schema::rename(V1Tables::renamed($table), $table);
            }
        }
    }
};
