# SEO del frontend

## Estrategia (SEO-01)

TecAlliance acepta mantener Vite con prerenderizado o adoptar SSR, siempre que el HTML que reciben los
buscadores incluya título, descripción, metadatos sociales y contenido relevante. Fenix elige **mantener
el frontend en Vite (SPA) y que Laravel sirva su `index.html` con los metadatos y el contenido de cada
ruta ya insertados**:

- El build de `tectdoc-front` se copia en `public/app` y Laravel atiende cualquier ruta que no sea `/api`,
  `/up` ni `/robots.txt` con `SpaController`.
- `App\Seo\SeoResolver` genera, para cada ruta de Vue Router, el título, la descripción y un resumen del
  contenido. En marcas, modelos y productos consulta TecDoc a través de `TecdocClient`, que ya tiene caché.
- `App\Seo\SpaRenderer` inserta en el HTML los metadatos, `<link rel="canonical">`, la configuración
  pública del cliente (`window.__CLIENT_CONFIG__`) y el contenido dentro de `<div id="app">`. Vue lo
  reemplaza al montarse, de modo que el usuario ve la misma aplicación de siempre.
- Las búsquedas internas se marcan `noindex, follow` y las rutas inexistentes responden 404.

Por qué no prerenderizado ni SSR:

- **Prerenderizado en el build**: solo cubre rutas fijas; las páginas de producto y vehículo son miles y
  dependen de TecDoc, así que quedarían sin HTML propio.
- **SSR (Nuxt)**: cumple todo, pero exige migrar el frontend completo y un servidor Node; no cabe en el plazo
  de la fase.

Impacto en el plazo: ninguno sobre los 7 días de la fase. La única condición nueva es que el frontend se
despliega detrás de Laravel (ver más abajo).

Limitaciones conocidas:

- La primera visita a un producto o a los modelos de una marca espera la respuesta de TecDoc (hasta ~1,5 s);
  las siguientes salen de la caché del proxy (`TECDOC_CACHE_TTL`).
- El título del navegador se genera en el servidor; al navegar dentro de la SPA no se actualiza todavía.

## Despliegue del frontend

En `tectdoc-front`, con las variables del entorno correspondiente:

```bash
VITE_BASE=/app/ VITE_API_URL=/api npm run build
```

En Git Bash para Windows `/app/` se convierte en una ruta de Windows; ejecuta el build desde PowerShell o
antepón `MSYS_NO_PATHCONV=1`.

Copia el contenido de `dist/` en `public/app/` de este proyecto (`/public/app` está en `.gitignore`).
Si el build se deja en otra carpeta, indica su `index.html` en `SPA_INDEX_PATH`.

## Bloqueo de indexación en entornos de prueba (SEO-02)

Fuera de producción (`SEO_ALLOW_INDEXING=false`, valor por defecto):

- Todas las respuestas del backend llevan `X-Robots-Tag: noindex, nofollow`.
- `/robots.txt` responde `Disallow: /`.
- El HTML del frontend lleva `<meta name="robots" content="noindex, nofollow">`.

El build del frontend hace lo mismo por su cuenta (meta robots y `robots.txt`) salvo que se compile con
`VITE_ALLOW_INDEXING=true`, así que también queda protegido si se publica sin Laravel.

Comprobación:

```bash
curl -sI https://<entorno>/ | grep -i x-robots-tag
curl -s https://<entorno>/robots.txt
curl -s https://<entorno>/ | grep -o '<meta name="robots"[^>]*>'
```

Resultado esperado en pruebas: `X-Robots-Tag: noindex, nofollow`, `Disallow: /` y
`content="noindex, nofollow"`. En producción se activa con `SEO_ALLOW_INDEXING=true` y
`VITE_ALLOW_INDEXING=true`.

## Comprobación del HTML por ruta

```bash
curl -s https://<entorno>/product/<referencia> | grep -E '<title>|name="description"|<h1>'
```
