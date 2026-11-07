<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tithe receipts (issued by users with the finance scope) and the proofs of
     * payment members upload for them. Files live on the "receipts" disk.
     */
    public function up(): void
    {
        if (! Schema::hasTable('payment_proofs')) {
            Schema::create('payment_proofs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('member_id');
                $table->unsignedBigInteger('user_id');          // who uploaded it
                $table->string('file_path');
                $table->string('file_name')->nullable();
                $table->string('mime', 100)->nullable();
                $table->decimal('amount', 14, 2)->nullable();
                $table->date('paid_on')->nullable();
                $table->text('note')->nullable();
                $table->string('status', 16)->default('pending'); // pending | acknowledged | receipted
                $table->unsignedBigInteger('acknowledged_by')->nullable();
                $table->timestamp('acknowledged_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'created_at']);
                $table->index('member_id');
            });
        }

        if (! Schema::hasTable('tithe_receipts')) {
            Schema::create('tithe_receipts', function (Blueprint $table) {
                $table->id();
                $table->string('number', 32)->unique();          // ICA-2026-00001
                $table->unsignedBigInteger('member_id');
                $table->decimal('amount', 14, 2);
                $table->string('currency', 3)->default('MWK');
                $table->date('received_on');
                $table->string('method', 16);                    // cash | bank | mobile
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('issued_by');
                $table->string('pdf_path')->nullable();
                $table->unsignedBigInteger('payment_proof_id')->nullable();
                $table->timestamps();
                $table->index(['member_id', 'received_on']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tithe_receipts');
        Schema::dropIfExists('payment_proofs');
    }
};
