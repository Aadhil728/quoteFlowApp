<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_public_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_revision_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'quotation_id', 'revoked_at'], 'quote_public_token_lookup_idx');
        });

        Schema::create('quotation_optional_selections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_public_token_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_revision_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_item_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_selected')->default(false);
            $table->timestamps();
            $table->unique(['quotation_public_token_id', 'quotation_item_id'], 'quote_optional_token_item_unique');
        });

        Schema::create('quotation_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_revision_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_public_token_id')->constrained()->cascadeOnDelete();
            $table->string('author_name', 120);
            $table->string('author_email')->nullable();
            $table->text('message');
            $table->timestamps();
            $table->index(['workspace_id', 'quotation_id', 'created_at'], 'quote_comments_timeline_idx');
        });

        Schema::create('quotation_acceptances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_revision_id')->constrained()->restrictOnDelete();
            $table->foreignId('quotation_public_token_id')->constrained()->restrictOnDelete();
            $table->string('decision', 20);
            $table->string('printed_name', 120);
            $table->boolean('terms_accepted')->default(false);
            $table->text('reason')->nullable();
            $table->json('snapshot');
            $table->char('snapshot_hash', 64);
            $table->char('ip_hash', 64)->nullable();
            $table->char('user_agent_hash', 64)->nullable();
            $table->timestamp('decided_at');
            $table->timestamps();
            $table->unique('quotation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_acceptances');
        Schema::dropIfExists('quotation_comments');
        Schema::dropIfExists('quotation_optional_selections');
        Schema::dropIfExists('quotation_public_tokens');
    }
};
