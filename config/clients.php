<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuración por cliente
    |--------------------------------------------------------------------------
    |
    | Cada cliente es una entrada de "clients" con sus propios valores, que se
    | leen de variables de entorno. Para registrar un cliente nuevo se agrega
    | una entrada con sus variables; no hay que modificar la integración con
    | TecDoc.
    |
    | Todo lo que está dentro de "tecdoc" es secreto y nunca se envía al
    | navegador. El resto se expone en GET /api/config/{client}.
    |
    */

    // Cliente usado cuando no se puede determinar por dominio y en procesos internos
    'default' => env('DEFAULT_CLIENT', env('TECDOC_DEFAULT_CLIENT', 'fenix')),

    'clients' => [
        'fenix' => [
            'name' => env('FENIX_NAME', 'Fenix'),

            // Dominios (separados por coma) que sirven el sitio de este cliente
            'domains' => env('FENIX_DOMAINS', ''),

            'country' => env('FENIX_COUNTRY', 'CO'),
            'lang' => env('FENIX_LANG', 'es'),

            'tecdoc' => [
                'api_key' => env('TECDOC_FENIX_API_KEY'),
                'provider' => (int) env('TECDOC_FENIX_PROVIDER', 25100),
            ],

            'gtm_id' => env('FENIX_GTM_ID'),
            'whatsapp' => env('FENIX_WHATSAPP'),

            'footer' => [
                'company' => env('FENIX_FOOTER_COMPANY'),
                'tax_id' => env('FENIX_FOOTER_TAX_ID'),
                'address' => env('FENIX_FOOTER_ADDRESS'),
                'phone' => env('FENIX_FOOTER_PHONE'),
                'email' => env('FENIX_FOOTER_EMAIL'),
                'facebook' => env('FENIX_FOOTER_FACEBOOK'),
                'instagram' => env('FENIX_FOOTER_INSTAGRAM'),
            ],

            'hubspot' => [
                'portal_id' => env('FENIX_HUBSPOT_PORTAL_ID'),
                'form_id' => env('FENIX_HUBSPOT_FORM_ID'),
            ],
        ],
    ],

];
