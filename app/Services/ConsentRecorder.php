<?php

namespace App\Services;

use App\Models\Consent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Guarda un consentimiento explícito (LGPD-03) con la fecha del servidor y la versión
 * de la política de privacidad vigente.
 */
class ConsentRecorder
{
    public function __construct(private LegalDocuments $documents)
    {
    }

    /**
     * @return Consent|null null si el cliente no existe
     */
    public function record(Request $request, string $client, string $purpose, ?string $email = null, ?string $reference = null): ?Consent
    {
        $privacy = $this->documents->find($client, 'privacidad');

        if (! $privacy) {
            return null;
        }

        return Consent::create([
            'client' => $client,
            'purpose' => $purpose,
            'policy_version' => $privacy['version'],
            'email' => $email,
            'reference' => $reference,
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'accepted_at' => now(),
        ]);
    }
}
