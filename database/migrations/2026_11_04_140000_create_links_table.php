<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('original_url');
            $table->string('code', 32)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // The owner's dashboard lists links newest first.
            $table->index(['user_id', 'created_at']);
            // The purge command scans by expiry.
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
