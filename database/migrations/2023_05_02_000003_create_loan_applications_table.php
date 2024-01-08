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
        Schema::create('loan_applications', function (Blueprint $table) { // Solicitudes
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->comment('ID del usuario');
            $table->foreignId('quotation_id')->nullable()->constrained()->comment('ID de la cotización');
            $table->foreignId('bank_id')->constrained()->comment('ID del banco');
            $table->json('amortization_data')->nullable()->comment('Datos de amortización');
            $table->integer('current_stage')->default(1)->comment('Estado de la solicitud');
            $table->foreignId('start_application_id')->nullable()->constrained('attachments')->nullOnDelete()->comment('Solicitud llenada'); // Solicitud que sube el experto
            $table->foreignId('end_application_id')->nullable()->constrained('attachments')->nullOnDelete()->comment('Solicitud firmada'); // Solicitud que sube el cliente
            $table->boolean('confirm_application')->default(false)->comment('Indica si la solicitud está confirmada'); // Experto confirma la firma de la solicitud
            $table->foreignId('start_attached_id')->nullable()->constrained('attachments')->nullOnDelete()->comment('Anexo llenado'); // Anexo que sube el experto
            $table->foreignId('end_attached_id')->nullable()->constrained('attachments')->nullOnDelete()->comment('Anexo firmado'); // Anexo que sube el cliente
            $table->boolean('confirm_attached')->default(false)->comment('Indica si el anexo está confirmado'); // Experto confirma la firma del anexo
            $table->dateTime('signature_date')->nullable()->comment('Fecha de firma');
            $table->boolean('confirm_conditions')->default(false)->comment('Indica si las condiciones están confirmadas'); // Cliente confirma las condiciones
            $table->boolean('is_active')->default(true)->comment('Indica si la solicitud está activa');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_applications');
    }
};
