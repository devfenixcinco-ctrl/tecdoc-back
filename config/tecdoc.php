<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TecDoc (TecAlliance Pegasus 3.0)
    |--------------------------------------------------------------------------
    |
    | Todas las llamadas al webservice de TecDoc pasan por este backend. Las
    | claves solo viven en variables de entorno del servidor y nunca se
    | envían al navegador.
    |
    */

    'url' => env('TECDOC_URL', 'https://webservice.tecalliance.services/pegasus-3-0/services/TecdocToCatDLB.jsonEndpoint?js'),

    'timeout' => (int) env('TECDOC_TIMEOUT', 20),

    // Cliente usado por procesos internos (p. ej. sincronización de fabricantes)
    'default_client' => env('TECDOC_DEFAULT_CLIENT', 'fenix'),

    /*
    | Credenciales y parámetros aislados por cliente. Para agregar un cliente
    | se añade una entrada aquí con sus propias variables de entorno.
    */
    'clients' => [
        'fenix' => [
            'api_key' => env('TECDOC_FENIX_API_KEY'),
            'provider' => (int) env('TECDOC_FENIX_PROVIDER', 25100),
        ],
    ],

    // Métodos de TecDoc que el proxy acepta desde el frontend
    'allowed_methods' => [
        'getArticles',
        'getBrands',
        'getChildNodesPattern2',
        'getLinkageTargets',
        'getManufacturers2',
    ],

    // Segundos que se guarda en caché cada respuesta exitosa (0 desactiva la caché)
    'cache_ttl' => (int) env('TECDOC_CACHE_TTL', 3600),

    // Solicitudes por minuto permitidas por IP y cliente
    'rate_limit' => (int) env('TECDOC_RATE_LIMIT', 120),

];
