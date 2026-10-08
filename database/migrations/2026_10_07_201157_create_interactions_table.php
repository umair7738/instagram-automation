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
        Schema::create('interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instagram_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('media_resource_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('automation_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('automation_execution_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('source_media_id')->nullable()->index();
            $table->string('source_comment_id')->nullable()->unique();
            $table->string('source_message_id')->nullable()->unique();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->index(['contact_id', 'occurred_at']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interactions');
    }
};
