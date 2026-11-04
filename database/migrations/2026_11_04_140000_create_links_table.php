<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('original_url');
            // MySQL's default collation is case-insensitive, which would make "abc" and "ABC"
            // the same code and silently shrink the base62 key space. SQLite is binary already.
            $table->string('code', 32)
                ->collation(DB::getDriverName() === 'mysql' ? 'utf8mb4_bin' : null)
                ->unique();
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
