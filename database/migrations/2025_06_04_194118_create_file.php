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
        Schema::create('files', function (Blueprint $table) {
            $table->string("id")->primary();
            $table->string("name");
            $table->enum('type', ['file', 'folder'])->default('folder');
            // $table->string("path");
            $table->timestamps();


            $table->foreignId('parent_id')
                ->nullable()
                ->references('id')
                ->on('files')
                ->onDelete('cascade')
                ->onUpdate('cascade');


            $table->unique(['parent_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
