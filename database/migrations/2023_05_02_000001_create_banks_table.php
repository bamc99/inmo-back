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
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique()->comment('Slug del banco');
            $table->string('name')->comment('Nombre del banco');
            $table->string('contact_email')->nullable()->comment('Correo electrónico de contacto del banco');
            $table->string('contact_phone')->nullable()->comment('Teléfono de contacto del banco');
            $table->unsignedBigInteger('logo_id')->nullable();
            $table->unsignedBigInteger('pdf_id')->nullable();
            $table->foreign('logo_id')->references('id')->on('attachments')->onDelete('set null');
            $table->foreign('pdf_id')->references('id')->on('attachments')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};
