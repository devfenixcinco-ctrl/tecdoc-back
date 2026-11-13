<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Indexación por buscadores
    |--------------------------------------------------------------------------
    |
    | Solo el entorno de producción debe permitir la indexación. Mientras sea
    | false, todas las respuestas llevan X-Robots-Tag: noindex, nofollow y
    | robots.txt bloquea a todos los rastreadores.
    |
    */

    'allow_indexing' => (bool) env('SEO_ALLOW_INDEXING', false),

];
