<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{    
    public function up()
    {
        Schema::table('enrollment_batches', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->foreignId('paid_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
        });
    }
    
    public function down()
    {
        Schema::table('enrollment_batches', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['paid_by']);
            $table->dropColumn(['updated_by', 'paid_by']);
        });
    }
};
