<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //whenever job will be entered the value of status will be 1 by default
        Schema::table('jobs', function (Blueprint $table) {
            $table->integer('status')->default(1)->after('company_website');
            $table->integer('isFeatured')->after('status')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //If migrations rollbacks then the columns below will be dropped
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->integer('isFeatured');
        });
    }
};
