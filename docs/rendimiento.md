# Rendimiento y Core Web Vitals (Fase 4, PERF-01)

Objetivos de referencia del documento: **LCP ≤ 2,5 s**, **INP ≤ 200 ms**, **CLS ≤ 0,1**. Los resultados
dependen también del hosting, la red, la CDN y el contenido final.

## Cómo se mide

- Herramienta: **Lighthouse 12.8** (Chrome), categoría Performance.
- Perfiles: **móvil** (por defecto de Lighthouse: Moto G, 4G lento, CPU ×4) y **escritorio** (`--preset=desktop`).
- Páginas: inicio (`/`), catálogo (`/catalogo`) y producto (`/product/FT00002`).
- **3 corridas por página y perfil; se reporta la mediana.**
- INP no se puede medir en laboratorio (requiere interacciones reales). Lighthouse usa el **TBT**
  (tiempo total de bloqueo) como indicador. El INP real se obtendrá con datos de usuarios
  (PageSpeed Insights / Chrome UX Report o GA4 en la Fase 6).

Comando por corrida:

```bash
npx lighthouse <url> --only-categories=performance --output=json --output-path=<archivo>.json [--preset=desktop]
```

## Línea base en el hosting (19/11/2026, antes de la fase)

`https://tecdoc.pruebasfenix.com`

| Página | Perfil | Score | FCP | LCP | TBT | CLS | Peso |
|---|---|---|---|---|---|---|---|
| Inicio | móvil | 43 | 10,9 s | 14,8 s | 509 ms | 0,005 | 6,1 MB |
| Inicio | escritorio | 50 | 2,9 s | 4,7 s | 180 ms | 0,128 | 6,2 MB |
| Catálogo | móvil | 33 | 11,2 s | 12,9 s | 1087 ms | 0,000 | 3,5 MB |
| Catálogo | escritorio | 57 | 2,8 s | 3,3 s | 216 ms | 0,006 | 5,2 MB |
| Producto | móvil | 19 | 11,4 s | 15,0 s | 687 ms | 0,395 | 2,4 MB |
| Producto | escritorio | 62 | 2,9 s | 3,2 s | 0 ms | 0,107 | 2,4 MB |

Causas principales encontradas:

- El hosting no comprimía nada ni enviaba cabeceras de caché.
- CSS de 738 KB que bloqueaba el render (incluía la fuente completa de íconos MDI).
- Imágenes enormes: banderas SVG de hasta 621 KB, logos de marcas de hasta 1,6 MB, un fondo de 773 KB
  (una foto JPEG dentro de un SVG) y la imagen de producto pedida en 3200 px.
- La imagen decorativa que era el elemento LCP tenía `loading="lazy"`.
- Un único JS de 772 KB con todas las páginas y ~150 KB de datos de ciudades.
- El inicio hacía tres consultas a la API una tras otra.
- CLS en producto (0,39) por el bloque de imágenes que aparecía al llegar los datos, y en inicio (0,13)
  por el cambio de fuente del título.

## Cambios

| Área | Cambio |
|---|---|
| Compresión y caché | `.htaccess` del front (se genera en el build) y `public/.htaccess` del backend: gzip de HTML, CSS, JS, JSON y SVG; assets con hash en caché por un año; `index.html` sin caché |
| Imágenes | SVG pesados convertidos a WebP al tamaño real en pantalla (banderas 100 px, logos del carrusel 140 px, fondo 1600 px); PNG ajustados al tamaño mostrado; imagen de producto en 800 px |
| Íconos | La fuente completa de MDI se reemplazó por un CSS generado al compilar con solo los íconos usados (`vite-plugins/mdi-icons.js`) |
| Fuentes | `preconnect`, solo los pesos usados (Poppins 300–700, sin cursivas) y carga sin bloquear el render |
| JavaScript | Cada página se descarga al visitarla (el inicio va en el paquete inicial); los datos de ciudades se cargan aparte |
| API | El inicio pide sus tres consultas en paralelo; las respuestas de TecDoc se reutilizan durante la visita |
| LCP | Las imágenes que son el elemento LCP ya no usan `loading="lazy"`; la de producto lleva `fetchpriority="high"` |
| CLS | Espacio reservado para las imágenes del producto mientras cargan |
| Pantalla en blanco | `index.html` muestra una pantalla de carga mínima hasta que la app se monta |

Tamaño del build: **12 MB → 1,9 MB**. JS inicial: **772 KB → 478 KB** (≈160 KB comprimido).
CSS: **738 KB → 425 KB** (≈59 KB comprimido).

## Resultado (mismas condiciones, en local)

Mismo backend, misma máquina y mismas páginas. *Antes*: build anterior servido sin compresión (como
el hosting hoy). *Después*: build nuevo servido con compresión y caché (como quedará el hosting con el
nuevo `.htaccess`).

| Página | Perfil | FCP antes → después | LCP antes → después | TBT antes → después | CLS antes → después |
|---|---|---|---|---|---|
| Inicio | móvil | 12,5 → **5,6 s** | 23,3 → **10,3 s** | 1212 → **952 ms** | 0,004 → **0,000** |
| Inicio | escritorio | 3,5 → **1,0 s** | 4,6 → **3,2 s** | 251 → **160 ms** | 0,126 → 0,124 |
| Catálogo | móvil | 12,3 → **5,6 s** | 14,3 → **8,9 s** | 0 → 578 ms | 0,007 → **0,000** |
| Catálogo | escritorio | 3,1 → **1,1 s** | 3,5 → **2,8 s** | 109 → 125 ms | 0,010 → **0,008** |
| Producto | móvil | 12,4 → **5,6 s** | 15,7 → **10,2 s** | 793 → 830 ms | 0,415 → **0,031** |
| Producto | escritorio | 3,1 → **1,0 s** | 5,0 → **3,4 s** | 109 → 122 ms | 0,099 → **0,015** |

Peso de la página de inicio: **6,1 MB → 1,6 MB**.

Notas:

- Lighthouse en `localhost` informa los tamaños sin descontar la compresión, así que estas cifras son
  conservadoras; la medición definitiva es la del hosting después del despliegue.
- En local el JSON de la API no se comprime (el Apache de Laragon tiene `mod_deflate` desactivado). En el
  hosting la respuesta de grupos de TecDoc (~340 KB) baja a cerca de 35 KB.
- El TBT del catálogo en móvil sube porque ahora la página carga antes y la ejecución del JS cae dentro de
  la ventana medida; en escritorio se mantiene estable.
- INP: sin limitar la CPU, ninguna interacción probada (cambiar de pestaña, abrir y filtrar el selector de
  marcas) superó 16 ms.

## Pendiente para cumplir los objetivos en móvil

El LCP en móvil sigue por encima de 2,5 s. Lo que más pesa ahora es la ejecución del JavaScript inicial
(Vue + Vuetify, ~5 s con la CPU de un móvil de gama media simulada) y la espera de los datos de TecDoc
antes de pintar el contenido. Mejoras posibles para una siguiente iteración:

- Servir el front detrás de Laravel (SEO-01) para que el HTML llegue con contenido y no dependa del JS.
- Reducir los estilos de Vuetify a los componentes usados.
- CDN para los assets y compresión Brotli si el hosting lo permite.

## Cómo repetir la medición en el hosting

Después de desplegar:

```bash
curl -sI -H "Accept-Encoding: gzip" https://tecdoc.pruebasfenix.com/assets/<archivo>.js | grep -i content-encoding
npx lighthouse https://tecdoc.pruebasfenix.com/ --only-categories=performance --view
npx lighthouse https://tecdoc.pruebasfenix.com/ --only-categories=performance --preset=desktop --view
```

Repetir 3 veces por página y perfil y comparar la mediana con la línea base de arriba.
