<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_correct_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('attendance_id')->constrained()->onDelete('cascade');
            $t->foreignId('user_id')->constrained()->onDelete('cascade');
            $t->dateTime('new_start_time')->nullable();
            $t->dateTime('new_end_time')->nullable();
            $t->json('new_breaks')->nullable(); //[{"start":"HH:mm","end":"HH:mm"},...]
            $t->text('note');
            $t->enum('status',['pending','approved','rejected'])->default('pending');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_correct_requests');
    }
};