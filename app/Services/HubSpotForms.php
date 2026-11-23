<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envía un lead al formulario de HubSpot del cliente (API de envío de formularios).
 * HubSpot crea el contacto si el correo no existe y lo actualiza si ya existe.
 */
class HubSpotForms
{
    public function __construct(private ClientRegistry $clients)
    {
    }

    public function isConfigured(string $client): bool
    {
        $hubspot = $this->clients->get($client)['hubspot'] ?? [];

        return ! empty($hubspot['portal_id']) && ! empty($hubspot['form_id']);
    }

    /**
     * Envía el lead y guarda el resultado en crm_status / crm_error.
     */
    public function send(Lead $lead, ?string $consentText = null): Lead
    {
        if (! $this->isConfigured($lead->client)) {
            $lead->update(['crm_status' => 'pendiente', 'crm_error' => 'HubSpot no está configurado para el cliente']);

            return $lead;
        }

        $hubspot = $this->clients->get($lead->client)['hubspot'];
        $url = rtrim($hubspot['host'], '/')."/submissions/v3/integration/submit/{$hubspot['portal_id']}/{$hubspot['form_id']}";

        try {
            $response = Http::acceptJson()->timeout(15)->post($url, $this->payload($lead, $hubspot, $consentText));
        } catch (ConnectionException $e) {
            return $this->fail($lead, 'Sin conexión con HubSpot: '.$e->getMessage());
        }

        if (! $response->successful()) {
            $errors = collect($response->json('errors', []))->map(fn ($e) => ($e['errorType'] ?? '').': '.($e['message'] ?? ''))->join(' | ');

            return $this->fail($lead, "HubSpot respondió {$response->status()}. ".($errors ?: $response->body()));
        }

        $lead->update(['crm_status' => 'enviado', 'crm_error' => null, 'crm_sent_at' => now()]);

        return $lead;
    }

    private function payload(Lead $lead, array $hubspot, ?string $consentText): array
    {
        $map = $hubspot['fields'];
        $values = [
            'name' => $lead->name,
            'company' => $lead->company,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'segment' => $lead->segment,
            'utm_source' => $lead->utm_source,
            'utm_medium' => $lead->utm_medium,
            'utm_campaign' => $lead->utm_campaign,
            'landing_page' => $lead->landing_page,
            // Propiedad de fecha y hora en HubSpot: milisegundos desde 1970
            'consent_date' => (string) $lead->consent->accepted_at->getTimestampMs(),
        ];

        $fields = collect($values)
            ->filter(fn ($value, $key) => $value !== null && $value !== '' && ! empty($map[$key]))
            ->map(fn ($value, $key) => ['objectTypeId' => '0-1', 'name' => $map[$key], 'value' => (string) $value])
            ->values()
            ->all();

        $payload = [
            'submittedAt' => (string) $lead->created_at->getTimestampMs(),
            'fields' => $fields,
            'context' => array_filter([
                'pageUri' => $lead->page_uri,
                'pageName' => $lead->page_name,
            ]),
        ];

        if ($hubspot['legal_consent'] && $consentText) {
            $payload['legalConsentOptions'] = [
                'consent' => ['consentToProcess' => true, 'text' => $consentText],
            ];
        }

        return $payload;
    }

    private function fail(Lead $lead, string $error): Lead
    {
        Log::warning('No se pudo enviar el lead a HubSpot', ['lead' => $lead->id, 'error' => $error]);
        $lead->update(['crm_status' => 'error', 'crm_error' => mb_substr($error, 0, 2000)]);

        return $lead;
    }
}
