<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->constrained()->cascadeOnDelete();
            $table->char('ip_hash', 64);
            $table->text('user_agent')->nullable();
            $table->text('referrer')->nullable();
            $table->char('country', 2)->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('device', 20)->nullable();
            $table->timestamp('clicked_at');

            // Stats are always "clicks for this link in a date range".
            $table->index(['link_id', 'clicked_at']);
            // The purge command deletes by age.
            $table->index('clicked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clicks');
    }
};
