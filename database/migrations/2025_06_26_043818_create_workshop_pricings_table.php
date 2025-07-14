<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{    
    public function up(): void
    {
        Schema::create('workshop_pricings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('number_of_classes');
            $table->decimal('price', 8, 2);
            $table->boolean('is_default')->default(true);
            $table->boolean('for_volunteer_workshop')->default(false);
            $table->timestamps();
            
            $table->unique(['workshop_id', 'number_of_classes', 'for_volunteer_workshop'], 'unique_workshop_pricing_combo');
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('workshop_pricings');
    }
};
