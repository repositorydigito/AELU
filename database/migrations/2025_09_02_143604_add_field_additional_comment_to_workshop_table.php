<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workshops', function (Blueprint $table) {
            $table->string('additional_comments', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('workshop', function (Blueprint $table) {
            $table->dropColumn('additional_comments');
        });
    }
};
