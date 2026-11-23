<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\ConsentRecorder;
use App\Services\HubSpotForms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    /**
     * Formulario de contacto (CRM-01): registra el consentimiento (LGPD-03), guarda el
     * lead y lo envía a HubSpot. Si HubSpot falla, el lead queda guardado para
     * reenviarlo con `php artisan leads:reenviar`.
     */
    public function store(Request $request, string $client, ConsentRecorder $consents, HubSpotForms $hubspot)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:150'],
            'segment' => ['required', Rule::in(Lead::SEGMENTS)],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'accepted' => ['accepted'],
            'consent_text' => ['nullable', 'string', 'max:1000'],
            'utm_source' => ['nullable', 'string', 'max:150'],
            'utm_medium' => ['nullable', 'string', 'max:150'],
            'utm_campaign' => ['nullable', 'string', 'max:150'],
            'landing_page' => ['nullable', 'string', 'max:500'],
            'page_uri' => ['nullable', 'string', 'max:500'],
            'page_name' => ['nullable', 'string', 'max:200'],
            // Campo oculto contra bots: una persona nunca lo llena
            'website' => ['prohibited'],
        ]);

        $lead = DB::transaction(function () use ($request, $client, $consents, $data) {
            $consent = $consents->record($request, $client, 'contacto_comercial', $data['email']);

            if (! $consent) {
                return null;
            }

            $lead = Lead::create(collect($data)->except(['accepted', 'consent_text'])->all() + [
                'client' => $client,
                'consent_id' => $consent->id,
            ]);

            // El consentimiento queda asociado al envío
            $consent->update(['reference' => "lead-{$lead->id}"]);

            return $lead;
        });

        if (! $lead) {
            return response()->json(['error' => 'Cliente no encontrado'], 404);
        }

        $hubspot->send($lead, $data['consent_text'] ?? null);

        return response()->json([
            'id' => $lead->id,
            'segment' => $lead->segment,
            'consent_accepted_at' => $lead->consent->accepted_at->toIso8601String(),
            'crm_status' => $lead->crm_status,
        ], 201);
    }
}
