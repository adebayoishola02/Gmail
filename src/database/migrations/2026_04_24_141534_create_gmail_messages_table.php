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
        Schema::create('gmail_messages', function (Blueprint $table) {

            $table->id();
            $table->uuid('uuid')->unique();

            $table->uuid('company_uuid');
            $table->uuid('created_by_uuid');
            $table->uuid('account_uuid'); //Internal Gmail account_uuid

            // Gmail specific identifiers
            $table->string('google_message_id')->unique(); // ID from Gmail API
            $table->string('thread_id');                  // Thread ID from Gmail API

            // Email details
            $table->string('recipient');                  // 'To' address
            $table->string('sender');                     // 'From' address
            $table->string('subject')->nullable();
            $table->longText('body')->nullable();         // Use longText for HTML content 

            // Status tracking
            $table->enum('type', ['INBOX', 'SENT', 'DRAFT', 'TRASH', 'SPAM', 'UNREAD']);      // Categorize the message
            $table->timestamp('sent_at')->nullable();     // When Gmail processed it

            $table->timestamps();

            $table->index('company_uuid');
            $table->index('created_by_uuid');
        });

        Schema::table('gmail_messages', function (Blueprint $table) {
            $table->unique(
                ['company_uuid', 'google_message_id', 'thread_id'],
                'gmail_messages_unique_credential'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gmail_messages', function (Blueprint $table) {
            $table->dropUnique('gmail_messages_unique_credential');
        });

        Schema::dropIfExists('gmail_messages');
    }
};
