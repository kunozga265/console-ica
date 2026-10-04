<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ways to give shown on the site's Give page (managed in /admin):
     * bank accounts and mobile-money numbers.
     */
    public function up(): void
    {
        if (!Schema::hasTable('giving_options')) {
            Schema::create('giving_options', function (Blueprint $table) {
                $table->id();
                $table->string('type', 16);                 // 'bank' | 'mobile'
                $table->string('name');                     // bank or provider, e.g. "National Bank of Malawi", "Airtel Money"
                $table->string('account_name')->nullable(); // bank: account holder · mobile: merchant name
                $table->string('account_number');           // bank: account number · mobile: number to pay
                $table->string('branch')->nullable();       // bank only
                $table->string('swift_code')->nullable();   // bank only
                $table->text('instructions')->nullable();   // one step per line
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('giving_options');
    }
};
