<?php

namespace App\Services;

/**
 * Páginas por público (FUNC-03): /fabricantes, /distribuidores y /talleres. El texto
 * está en resources/audiences/{cliente}/{página}.md; mientras Fenix entrega el
 * contenido definitivo se usa el modelo de resources/audiences/default.
 */
class AudiencePages
{
    // Ruta de la página => segmento del formulario de contacto
    public const PAGES = [
        'fabricantes' => 'fabricante',
        'distribuidores' => 'distribuidor',
        'talleres' => 'taller',
    ];

    public function __construct(private ClientContent $content)
    {
    }

    /**
     * @return array{page: string, segment: string, title: string, html: string, version: string, updated_at: string}|null
     */
    public function find(string $client, string $page): ?array
    {
        if (! isset(self::PAGES[$page])) {
            return null;
        }

        $document = $this->content->find($client, 'audiences', $page);

        return $document ? ['page' => $page, 'segment' => self::PAGES[$page]] + $document : null;
    }
}
