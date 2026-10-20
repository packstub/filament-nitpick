<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Packstub\Nitpick\ThemeManager;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(ThemeManager::connectionName())->create(ThemeManager::tableName(), function (Blueprint $table): void {
            $table->id();
            $table->morphs('user');
            $table->string('panel_id');
            $table->string('theme_id');
            $table->timestamps();

            $table->unique(['user_type', 'user_id', 'panel_id']);
        });
    }

    public function down(): void
    {
        Schema::connection(ThemeManager::connectionName())->dropIfExists(ThemeManager::tableName());
    }
};
