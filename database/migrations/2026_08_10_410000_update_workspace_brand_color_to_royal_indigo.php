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
        DB::table('workspaces')->where('brand_color', '#078A68')->update(['brand_color' => '#4F46E5']);
        Schema::table('workspaces', fn (Blueprint $table) => $table->string('brand_color', 7)->default('#4F46E5')->change());
    }

    public function down(): void
    {
        Schema::table('workspaces', fn (Blueprint $table) => $table->string('brand_color', 7)->default('#078A68')->change());
    }
};
