<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('gmail_message_id')->nullable()->after('source');
            $table->unique(['user_id', 'gmail_message_id']);
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'gmail_message_id']);
            $table->dropColumn('gmail_message_id');
        });
    }
};
