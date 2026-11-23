<?php

namespace App\Services;

/**
 * Políticas legales por cliente (Markdown en resources/legal/{cliente}). Si el
 * cliente no tiene su propio texto se usa el modelo de resources/legal/default.
 */
class LegalDocuments
{
    public const POLICIES = ['privacidad', 'cookies'];

    public function __construct(private ClientContent $content)
    {
    }

    /**
     * @return array{policy: string, title: string, html: string, version: string, updated_at: string}|null
     */
    public function find(string $client, string $policy): ?array
    {
        if (! in_array($policy, self::POLICIES, true)) {
            return null;
        }

        $document = $this->content->find($client, 'legal', $policy);

        // "version" identifica el texto exacto que el usuario aceptó (LGPD-03)
        return $document ? ['policy' => $policy] + $document : null;
    }
}
