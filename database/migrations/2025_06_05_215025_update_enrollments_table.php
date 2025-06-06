<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {            
            $table->dropConstrainedForeignId('workshop_id');
            $table->foreignId('instructor_workshop_id')->after('student_id');               
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('instructor_workshop_id');            
            $table->foreignId('workshop_id')->nullable()->constrained()->onDelete('set null');
        });
    }
};
