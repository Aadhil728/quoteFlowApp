<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $t): void {
            $t->id();
            $t->ulid('ulid')->unique();
            $t->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_id')->constrained()->restrictOnDelete();
            $t->foreignId('quotation_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('quotation_acceptance_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->string('number', 40);
            $t->string('type', 20);
            $t->string('status', 20)->default('draft');
            $t->char('currency', 3);
            $t->date('issue_date');
            $t->date('due_date');
            $t->bigInteger('subtotal_minor');
            $t->bigInteger('tax_minor')->default(0);
            $t->bigInteger('total_minor');
            $t->bigInteger('paid_minor')->default(0);
            $t->bigInteger('balance_minor');
            $t->json('snapshot');
            $t->char('snapshot_hash', 64);
            $t->text('payment_instructions')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['workspace_id', 'number']);
            $t->unique('quotation_id');
            $t->index(['workspace_id', 'status', 'due_date']);
        });
        Schema::create('invoice_items', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $t->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->text('description')->nullable();
            $t->decimal('quantity', 12, 4);
            $t->string('unit', 30);
            $t->bigInteger('unit_price_minor');
            $t->unsignedInteger('tax_rate_basis_points')->default(0);
            $t->bigInteger('subtotal_minor');
            $t->bigInteger('tax_minor');
            $t->bigInteger('total_minor');
            $t->unsignedInteger('position')->default(0);
            $t->timestamps();
        });
        Schema::create('payments', function (Blueprint $t): void {
            $t->id();
            $t->ulid('ulid')->unique();
            $t->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $t->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('provider', 20);
            $t->string('status', 20);
            $t->char('currency', 3);
            $t->bigInteger('amount_minor');
            $t->string('reference')->nullable();
            $t->string('provider_payment_id')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->unique(['provider', 'provider_payment_id']);
            $t->index(['workspace_id', 'invoice_id', 'status']);
        });
        Schema::create('receipts', function (Blueprint $t): void {
            $t->id();
            $t->ulid('ulid')->unique();
            $t->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $t->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $t->foreignId('payment_id')->unique()->constrained()->restrictOnDelete();
            $t->string('number', 40);
            $t->json('snapshot');
            $t->char('snapshot_hash', 64);
            $t->timestamp('issued_at');
            $t->timestamps();
            $t->unique(['workspace_id', 'number']);
        });
        Schema::create('payment_provider_events', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();
            $t->string('provider', 20);
            $t->string('provider_event_id');
            $t->string('type');
            $t->char('payload_hash', 64);
            $t->json('normalized_payload')->nullable();
            $t->timestamp('processed_at')->nullable();
            $t->text('failure')->nullable();
            $t->timestamps();
            $t->unique(['provider', 'provider_event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_provider_events');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
