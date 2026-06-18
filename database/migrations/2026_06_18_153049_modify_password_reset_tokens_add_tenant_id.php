<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropPrimary(['email']);
            $table->foreignId('tenant_id')->after('email')->constrained()->cascadeOnDelete();
            $table->primary(['email', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropPrimary(['email', 'tenant_id']);
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
            $table->primary('email');
        });
    }
};
