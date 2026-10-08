<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('instagram_scoped_id');
            $table->string('username')->nullable();
            $table->string('name')->nullable();
            $table->timestamp('last_interacted_at')->nullable();
            $table->unique(['instagram_account_id', 'instagram_scoped_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
