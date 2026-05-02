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
            Schema::create('gmail_accounts', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();

                $table->uuid('company_uuid');
                $table->uuid('created_by_uuid');

                $table->string('email_address');
                $table->string('name')->nullable();
                $table->string('client_id', 1024);
                $table->string('client_secret', 1024);
                $table->string('access_token', 1024);
                $table->string('refresh_token', 1024);
                $table->string('token_expires_at');
                $table->string('last_sync_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('company_uuid');
                $table->index('created_by_uuid');
            });

            Schema::table('gmail_accounts', function (Blueprint $table) {
                $table->unique(
                    ['company_uuid', 'email_address'],
                    'gmail_accounts_unique_credential'
                );
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::table('gmail_accounts', function (Blueprint $table) {
                $table->dropUnique('gmail_accounts_unique_credential');
            });

            Schema::dropIfExists('gmail_accounts');
        }
    };
