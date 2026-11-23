<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Envíos del formulario de contacto (CRM-01). Se guardan antes de mandarlos a
     * HubSpot para no perderlos si HubSpot falla o aún no está configurado.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('client', 50);
            $table->string('name', 120);
            $table->string('company', 150)->nullable();
            // fabricante, distribuidor o taller
            $table->string('segment', 20);
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->string('utm_source', 150)->nullable();
            $table->string('utm_medium', 150)->nullable();
            $table->string('utm_campaign', 150)->nullable();
            // Primera página de la visita y página desde la que se envió el formulario
            $table->string('landing_page', 500)->nullable();
            $table->string('page_uri', 500)->nullable();
            $table->string('page_name', 200)->nullable();
            $table->foreignId('consent_id')->constrained('consents');
            // pendiente (HubSpot sin configurar), enviado o error
            $table->string('crm_status', 20)->default('pendiente');
            $table->text('crm_error')->nullable();
            $table->timestamp('crm_sent_at')->nullable();
            $table->timestamps();

            $table->index(['client', 'email']);
            $table->index(['client', 'crm_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
