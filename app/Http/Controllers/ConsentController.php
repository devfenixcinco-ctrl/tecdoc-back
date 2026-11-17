<?php

namespace App\Http\Controllers;

use App\Models\Consent;
use App\Services\LegalDocuments;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ConsentController extends Controller
{
    /**
     * Registra un consentimiento explícito con su fecha (LGPD-03). Solo se
     * acepta si la casilla fue marcada; la fecha la pone el servidor.
     */
    public function store(Request $request, string $client, LegalDocuments $documents)
    {
        $privacy = $documents->find($client, 'privacidad');

        if (! $privacy) {
            return response()->json(['error' => 'Cliente no encontrado'], 404);
        }

        $data = $request->validate([
            'accepted' => ['accepted'],
            'purpose' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        $consent = Consent::create([
            'client' => $client,
            'purpose' => $data['purpose'],
            'policy_version' => $privacy['version'],
            'email' => $data['email'] ?? null,
            'reference' => $data['reference'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'accepted_at' => now(),
        ]);

        return response()->json([
            'id' => $consent->id,
            'accepted_at' => $consent->accepted_at->toIso8601String(),
            'policy_version' => $consent->policy_version,
        ], 201);
    }
}
