<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workshop_templates', function (Blueprint $table) {
            $table->foreignId('delegate_user_id')
                ->nullable()
                ->after('instructor_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('workshop_templates', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\User::class, 'delegate_user_id');
            $table->dropColumn('delegate_user_id');
        });
    }
};
