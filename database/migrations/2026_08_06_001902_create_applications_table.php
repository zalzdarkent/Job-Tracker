<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recruiter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('position_title');
            $table->string('status')->default('applied');
            $table->date('application_date');
            $table->string('source')->nullable();
            $table->text('notes')->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'application_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
