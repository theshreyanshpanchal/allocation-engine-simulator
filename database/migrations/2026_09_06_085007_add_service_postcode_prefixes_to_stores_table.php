<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A simplified stand-in for FR-RGN-001/002's postal-code-range regions: a
     * comma-separated list of CEP prefixes this store serves (e.g. "01,02,03").
     * Null/empty means "no service area configured" — treated as unrestricted,
     * so every store created before this column existed keeps working exactly
     * as before.
     */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('service_postcode_prefixes')->nullable()->after('serviceable');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('service_postcode_prefixes');
        });
    }
};
