<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('current_revision_id')->nullable();
            $table->string('number', 40);
            $table->string('status', 32)->default('draft');
            $table->char('currency', 3);
            $table->string('reference')->nullable();
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->timestamp('last_saved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['workspace_id', 'number']);
            $table->index(['workspace_id', 'status', 'expiry_date']);
        });
        Schema::create('quotation_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('revision_number');
            $table->string('title');
            $table->text('introduction')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->text('exclusions')->nullable();
            $table->text('client_responsibilities')->nullable();
            $table->string('tax_mode', 20)->default('exclusive');
            $table->string('discount_type', 20)->nullable();
            $table->bigInteger('discount_value')->default(0);
            $table->bigInteger('subtotal_minor')->default(0);
            $table->bigInteger('discount_minor')->default(0);
            $table->bigInteger('tax_minor')->default(0);
            $table->bigInteger('total_minor')->default(0);
            $table->unsignedSmallInteger('deposit_percentage')->default(0);
            $table->bigInteger('deposit_minor')->default(0);
            $table->json('snapshot')->nullable();
            $table->char('content_hash', 64)->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
            $table->unique(['quotation_id', 'revision_number']);
            $table->index(['workspace_id', 'quotation_id']);
        });
        Schema::table('quotations', fn (Blueprint $table) => $table->foreign('current_revision_id')->references('id')->on('quotation_revisions')->nullOnDelete());
        Schema::create('quotation_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_revision_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->index(['workspace_id', 'quotation_revision_id', 'position'], 'quote_sections_revision_position_idx');
        });
        Schema::create('quotation_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_revision_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_section_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 12, 4)->default(1);
            $table->string('unit', 30)->default('item');
            $table->bigInteger('unit_price_minor')->default(0);
            $table->unsignedInteger('tax_rate_basis_points')->default(0);
            $table->boolean('is_optional')->default(false);
            $table->boolean('is_selected')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->bigInteger('line_subtotal_minor')->default(0);
            $table->bigInteger('line_tax_minor')->default(0);
            $table->bigInteger('line_total_minor')->default(0);
            $table->timestamps();
            $table->index(['workspace_id', 'quotation_revision_id', 'position'], 'quote_items_revision_position_idx');
        });
        Schema::create('quotation_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_revision_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 80);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['workspace_id', 'quotation_id', 'created_at']);
        });
        Schema::create('templates', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('style', 30)->default('professional');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['workspace_id', 'name']);
        });
        Schema::create('template_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('content');
            $table->timestamps();
            $table->unique(['template_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_versions');
        Schema::dropIfExists('templates');
        Schema::dropIfExists('quotation_events');
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotation_sections');
        Schema::table('quotations', fn (Blueprint $table) => $table->dropForeign(['current_revision_id']));
        Schema::dropIfExists('quotation_revisions');
        Schema::dropIfExists('quotations');
    }
};
