<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->string('category')->default('operational'); // operational, salary, purchase, etc
            $table->foreignId('user_id')->constrained(); // siapa yang mencatat
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};