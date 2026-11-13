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

    /*
    |--------------------------------------------------------------------------
    | Frontend
    |--------------------------------------------------------------------------
    |
    | index.html del build de Vite (tectdoc-front) que Laravel sirve con los
    | metadatos de cada ruta. Por defecto el build se copia en public/app.
    |
    */

    'spa_index' => env('SPA_INDEX_PATH') ?: public_path('app/index.html'),

];
