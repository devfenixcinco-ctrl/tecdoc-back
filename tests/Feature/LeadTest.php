<?php

namespace Tests\Feature;

use App\Models\Consent;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    private const HUBSPOT_URL = 'https://api.hsforms.com/submissions/v3/integration/submit/123/form-abc';

    private function configureHubSpot(): void
    {
        config([
            'clients.clients.fenix.hubspot.portal_id' => '123',
            'clients.clients.fenix.hubspot.form_id' => 'form-abc',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ana Pérez',
            'company' => 'Taller Central',
            'segment' => 'taller',
            'email' => 'ana@example.com',
            'phone' => '3001234567',
            'accepted' => true,
            'consent_text' => 'Autorizo el tratamiento de mis datos',
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'talleres-2026',
            'landing_page' => 'https://tecdoc.pruebasfenix.com/talleres?utm_source=google',
            'page_uri' => 'https://tecdoc.pruebasfenix.com/contacto',
            'page_name' => 'Contacto',
        ], $overrides);
    }

    public function test_guarda_el_lead_con_su_consentimiento_y_lo_envia_a_hubspot(): void
    {
        $this->configureHubSpot();
        Http::fake([self::HUBSPOT_URL => Http::response(['inlineMessage' => 'Gracias'])]);

        $response = $this->postJson('/api/leads/fenix', $this->payload());

        $response->assertCreated()->assertJson(['segment' => 'taller', 'crm_status' => 'enviado']);

        $lead = Lead::first();
        $this->assertSame('ana@example.com', $lead->email);
        $this->assertSame('google', $lead->utm_source);
        $this->assertSame('contacto_comercial', $lead->consent->purpose);
        $this->assertSame("lead-{$lead->id}", $lead->consent->reference);
        $this->assertNotNull($lead->crm_sent_at);

        Http::assertSent(function (Request $request) use ($lead) {
            $fields = collect($request['fields'])->pluck('value', 'name');

            return $request->url() === self::HUBSPOT_URL
                && $fields['email'] === 'ana@example.com'
                && $fields['firstname'] === 'Ana Pérez'
                && $fields['segmento'] === 'taller'
                && $fields['utm_campaign'] === 'talleres-2026'
                && $fields['pagina_de_entrada'] === 'https://tecdoc.pruebasfenix.com/talleres?utm_source=google'
                && $fields['fecha_consentimiento'] === (string) $lead->consent->accepted_at->getTimestampMs()
                && $request['context']['pageUri'] === 'https://tecdoc.pruebasfenix.com/contacto'
                && ! isset($request['legalConsentOptions']);
        });
    }

    public function test_envia_las_opciones_de_privacidad_si_el_formulario_las_usa(): void
    {
        $this->configureHubSpot();
        config(['clients.clients.fenix.hubspot.legal_consent' => true]);
        Http::fake([self::HUBSPOT_URL => Http::response([])]);

        $this->postJson('/api/leads/fenix', $this->payload())->assertCreated();

        Http::assertSent(fn (Request $request) => $request['legalConsentOptions']['consent'] === [
            'consentToProcess' => true,
            'text' => 'Autorizo el tratamiento de mis datos',
        ]);
    }

    public function test_no_se_envia_sin_consentimiento(): void
    {
        Http::fake();

        $this->postJson('/api/leads/fenix', $this->payload(['accepted' => false]))
            ->assertUnprocessable()->assertJsonValidationErrors('accepted');

        $this->assertSame(0, Lead::count());
        $this->assertSame(0, Consent::count());
        Http::assertNothingSent();
    }

    public function test_valida_el_segmento_y_el_correo(): void
    {
        $this->postJson('/api/leads/fenix', $this->payload(['segment' => 'otro', 'email' => 'no-es-correo']))
            ->assertUnprocessable()->assertJsonValidationErrors(['segment', 'email']);
    }

    public function test_rechaza_envios_de_bots(): void
    {
        $this->postJson('/api/leads/fenix', $this->payload(['website' => 'http://spam.example']))
            ->assertUnprocessable()->assertJsonValidationErrors('website');

        $this->assertSame(0, Lead::count());
    }

    public function test_guarda_el_lead_aunque_hubspot_no_este_configurado(): void
    {
        Http::fake();

        $this->postJson('/api/leads/fenix', $this->payload())
            ->assertCreated()->assertJson(['crm_status' => 'pendiente']);

        $this->assertSame(1, Lead::count());
        Http::assertNothingSent();
    }

    public function test_registra_el_error_si_hubspot_rechaza_el_envio(): void
    {
        $this->configureHubSpot();
        Http::fake([self::HUBSPOT_URL => Http::response([
            'errors' => [['message' => 'Field not in form', 'errorType' => 'FIELD_NOT_IN_FORM_DEFINITION']],
        ], 400)]);

        $this->postJson('/api/leads/fenix', $this->payload())
            ->assertCreated()->assertJson(['crm_status' => 'error']);

        $this->assertStringContainsString('FIELD_NOT_IN_FORM_DEFINITION', Lead::first()->crm_error);
    }

    public function test_cliente_inexistente(): void
    {
        $this->postJson('/api/leads/otro', $this->payload())->assertNotFound();
    }

    public function test_reenvia_los_leads_pendientes(): void
    {
        Http::fake([self::HUBSPOT_URL => Http::response([])]);
        $this->postJson('/api/leads/fenix', $this->payload())->assertJson(['crm_status' => 'pendiente']);

        $this->configureHubSpot();
        $this->artisan('leads:reenviar')->assertSuccessful();

        $this->assertSame('enviado', Lead::first()->crm_status);
        Http::assertSentCount(1);
    }
}
