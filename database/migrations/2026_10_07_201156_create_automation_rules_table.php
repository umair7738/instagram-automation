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
        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('media_resource_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('message_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resource_id')->nullable()->constrained('media_resources')->nullOnDelete();
            $table->string('name');
            $table->string('trigger_type')->default('comment_keyword');
            $table->string('keyword')->nullable();
            $table->text('public_reply')->nullable();
            $table->boolean('is_active')->default(true);
            $table->index(['media_resource_id', 'trigger_type', 'is_active']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_rules');
    }
};
