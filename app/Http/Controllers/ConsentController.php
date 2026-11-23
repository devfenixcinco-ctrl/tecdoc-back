<?php

namespace App\Http\Controllers;

use App\Services\ConsentRecorder;
use App\Services\LegalDocuments;
use Illuminate\Http\Request;

class ConsentController extends Controller
{
    /**
     * Registra un consentimiento explícito con su fecha (LGPD-03). Solo se
     * acepta si la casilla fue marcada; la fecha la pone el servidor.
     */
    public function store(Request $request, string $client, LegalDocuments $documents, ConsentRecorder $consents)
    {
        if (! $documents->find($client, 'privacidad')) {
            return response()->json(['error' => 'Cliente no encontrado'], 404);
        }

        $data = $request->validate([
            'accepted' => ['accepted'],
            'purpose' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        $consent = $consents->record($request, $client, $data['purpose'], $data['email'] ?? null, $data['reference'] ?? null);

        return response()->json([
            'id' => $consent->id,
            'accepted_at' => $consent->accepted_at->toIso8601String(),
            'policy_version' => $consent->policy_version,
        ], 201);
    }
}
