<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro de consentimientos explícitos de los formularios (LGPD-03).
     */
    public function up(): void
    {
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->string('client', 50);
            // Para qué se dio el consentimiento, p. ej. "contacto_comercial"
            $table->string('purpose', 100);
            // Versión de la política de privacidad vigente cuando se aceptó
            $table->string('policy_version', 20);
            $table->string('email')->nullable();
            // Identificador del envío o lead al que se asocia (p. ej. el de HubSpot)
            $table->string('reference', 100)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('accepted_at');
            $table->timestamps();

            $table->index(['client', 'email']);
            $table->index(['client', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};
