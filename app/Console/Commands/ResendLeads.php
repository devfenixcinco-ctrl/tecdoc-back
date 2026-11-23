<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Services\HubSpotForms;
use Illuminate\Console\Command;

class ResendLeads extends Command
{
    protected $signature = 'leads:reenviar {--client= : Solo los leads de este cliente}';

    protected $description = 'Reenvía a HubSpot los leads que quedaron pendientes o con error';

    public function handle(HubSpotForms $hubspot): int
    {
        $leads = Lead::query()
            ->whereIn('crm_status', ['pendiente', 'error'])
            ->when($this->option('client'), fn ($q, $client) => $q->where('client', $client))
            ->orderBy('id')
            ->get();

        if ($leads->isEmpty()) {
            $this->info('No hay leads pendientes.');

            return self::SUCCESS;
        }

        foreach ($leads as $lead) {
            $hubspot->send($lead);
            $this->line("Lead {$lead->id}: {$lead->crm_status}".($lead->crm_error ? " ({$lead->crm_error})" : ''));
        }

        return self::SUCCESS;
    }
}
