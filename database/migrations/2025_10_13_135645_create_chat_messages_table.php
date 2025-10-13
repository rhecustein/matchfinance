<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('chat_session_id')
                  ->constrained('chat_sessions')
                  ->onDelete('cascade');
            
            // Message Content
            $table->enum('role', ['user', 'assistant', 'system'])
                  ->comment('Message sender: user, AI assistant, or system');
            $table->text('content')->comment('Message text content');
            
            // References & Citations (for assistant responses)
            $table->json('referenced_transactions')->nullable()->comment('Array of transaction IDs referenced in response');
            $table->json('referenced_categories')->nullable()->comment('Categories discussed in response');
            $table->json('referenced_accounts')->nullable()->comment('Accounts discussed in response');
            
            // AI Metadata (for assistant messages)
            $table->integer('prompt_tokens')->default(0)->comment('Tokens used in prompt');
            $table->integer('completion_tokens')->default(0)->comment('Tokens used in completion');
            $table->integer('total_tokens')->default(0)->comment('Total tokens for this message');
            $table->decimal('cost', 10, 6)->default(0)->comment('Cost in USD for this message');
            $table->string('model', 50)->nullable()->comment('AI model used for this response');
            $table->integer('response_time_ms')->nullable()->comment('Time taken to generate response in milliseconds');
            
            // User Feedback (optional)
            $table->enum('user_rating', ['helpful', 'not_helpful'])->nullable()->comment('User feedback on assistant response');
            $table->text('user_feedback')->nullable()->comment('User feedback text');
            
            // Processing Status (for streaming)
            $table->enum('status', ['pending', 'streaming', 'completed', 'failed'])
                  ->default('completed')
                  ->comment('Message processing status');
            $table->text('error_message')->nullable()->comment('Error message if failed');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['chat_session_id', 'created_at'], 'idx_session_created');
            $table->index(['chat_session_id', 'role'], 'idx_session_role');
            $table->index(['status', 'created_at'], 'idx_status_created');
            $table->index(['role', 'user_rating'], 'idx_role_rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};