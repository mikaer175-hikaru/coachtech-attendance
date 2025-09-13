<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('break_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->onDelete('cascade');
            $table->dateTime('break_start')->nullable();
            $table->dateTime('break_end')->nullable();
            $table->timestamps();

            $table->index(['attendance_id', 'break_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('break_times');
    }
};
