<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    public const SEGMENTS = ['fabricante', 'distribuidor', 'taller'];

    protected $fillable = [
        'client',
        'name',
        'company',
        'segment',
        'email',
        'phone',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'landing_page',
        'page_uri',
        'page_name',
        'consent_id',
        'crm_status',
        'crm_error',
        'crm_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'crm_sent_at' => 'datetime',
        ];
    }

    public function consent(): BelongsTo
    {
        return $this->belongsTo(Consent::class);
    }
}
