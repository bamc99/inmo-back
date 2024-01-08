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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->integer('additional_income')->nullable()->default(0)->comment('Ingresos adicionales');
            $table->integer('additional_property_value')->nullable()->default(0)->comment('Valor de la vivienda adicional');
            $table->foreignId('client_id')->constrained()->comment('Cliente que creó la cotización');
            $table->integer('construction_area')->nullable()->default(0)->comment('Área de construcción');
            $table->integer('credit_import')->nullable()->default(0)->comment('Importe del crédito');
            $table->string('credit_type')->nullable()->comment('Tipo de crédito (por ejemplo, adquisición tradicional)');
            $table->integer('current_debt')->nullable()->default(0)->comment('Adeudo actual');
            $table->integer('down_payment')->nullable()->default(0)->comment('Enganche');
            $table->integer('infonavit_credit')->nullable()->default(0)->comment('Crédito Infonavit');
            $table->integer('land_area')->nullable()->default(0)->comment('Área del terreno');
            $table->integer('loan_amount')->nullable()->default(0)->comment('Monto del crédito');
            $table->integer('loan_term')->nullable()->default(0)->comment('Plazo del préstamo en años');
            $table->integer('notarial_fees_percentage')->nullable()->default(6)->comment('Porcentaje Gastos notariales');
            $table->integer('monthly_income')->nullable()->default(0)->comment('Ingresos mensuales');
            $table->integer('project_value')->nullable()->default(0)->comment('Valor del proyecto');
            $table->integer('property_value')->nullable()->default(0)->comment('Valor de la vivienda');
            $table->integer('remodeling_budget')->nullable()->default(0)->comment('Presupuesto de remodelación');
            $table->string('scheme')->nullable()->comment('Esquema de pagos (fijos o crecientes)');
            $table->string('state')->nullable()->comment('Estado o región donde se encuentra la propiedad');
            $table->integer('sub_account')->nullable()->default(0)->comment('Sub Cuenta');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
