<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('is_renewal');
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->onDelete('set null')->after('cancelled_at');
            $table->text('cancellation_reason')->nullable()->after('cancelled_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by_user_id']);
            $table->dropColumn(['cancelled_at', 'cancelled_by_user_id', 'cancellation_reason']);
        });
    }
};
