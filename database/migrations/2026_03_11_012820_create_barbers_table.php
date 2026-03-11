<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('barbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->unique(); // satu user hanya bisa jadi satu barberman
            $table->string('specialty')->nullable();
            $table->date('hire_date')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(0); // persentase komisi
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barbers');
    }
};