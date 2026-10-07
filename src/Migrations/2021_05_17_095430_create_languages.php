<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateLanguages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('language', 10);
            $table->tinyInteger('is_active');
            $table->integer('priority')->default(0);
        });

        // Добавление индекса с предварительной проверкой
        $indexName = 'languages_idx_is_active_priority';

        $hasIndex = DB::selectOne("
            SELECT COUNT(1) as count
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE table_schema = DATABASE()
              AND table_name = 'languages'
              AND index_name = ?
        ", [$indexName]);

        if ($hasIndex->count == 0) {
            Schema::table('languages', function (Blueprint $table) use ($indexName) {
                $table->index(['is_active', 'priority'], $indexName);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->dropIndex('languages_idx_is_active_priority');
        });

        Schema::dropIfExists('languages');
    }
}
