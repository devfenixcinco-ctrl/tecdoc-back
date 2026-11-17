# Consentimiento y protección de datos (Fase 3)

## Banner de cookies (LGPD-01)

El banner lo muestra un CMP de terceros, como sugiere TecAlliance: **CookieYes**, **Cookiebot** o
**Usercentrics**. Cada cliente usa su propia cuenta:

```
FENIX_CMP_PROVIDER=cookieyes      # cookieyes | cookiebot | usercentrics
FENIX_CMP_ID=<id de la cuenta>    # CookieYes: clave del sitio · Cookiebot: Domain Group ID · Usercentrics: Settings ID
```

El frontend inserta el script del CMP al arrancar (`src/services/cookieConsent.js`). Si no hay CMP
configurado no se carga ninguno y el botón "Configurar cookies" del pie de página no aparece.

En el panel del CMP hay que dejar configurado:

- Botones **Aceptar**, **Rechazar** y **Personalizar** visibles en el primer nivel y con el mismo estilo
  y tamaño (mismo nivel de destaque).
- Las categorías **Necesarias**, **Analítica** y **Publicidad**.
- **Google Consent Mode v2 activado**, para que el CMP envíe `gtag('consent', 'update', ...)` cuando el
  usuario decide.
- Los textos del banner del cliente (para el prototipo vale el texto modelo del CMP).
- En Cookiebot, sin bloqueo automático (`data-blockingmode="auto"`): el sitio ya controla la carga de GTM.

La licencia del CMP, si aplica, no está incluida en la estimación.

## Políticas (LGPD-02)

- Textos modelo: `resources/legal/default/privacidad.md` y `resources/legal/default/cookies.md`.
- Para un cliente con textos propios se crea `resources/legal/{cliente}/privacidad.md` (y/o `cookies.md`).
- Marcadores que se completan con la configuración del cliente: `{site}`, `{company}`, `{tax_id}`,
  `{address}`, `{email}`, `{phone}`, `{dpo_name}`, `{dpo_email}`, `{updated}`. Los que no tienen valor
  aparecen como `[pendiente]`.
- Se publican en `/politica-de-privacidad` y `/politica-de-cookies`, enlazadas desde el pie de página, y
  en `GET /api/legal/{cliente}/{privacidad|cookies}`.

La versión definitiva la redacta y aprueba cada cliente como responsable del tratamiento.

## Consentimiento en formularios (LGPD-03)

- Frontend: componente `ConsentCheckbox` (desmarcado por defecto, obligatorio, con la finalidad y el
  enlace a la política) y `recordConsent()` en `src/services/consentRecord.js`.
- Backend: `POST /api/consents/{cliente}` con `accepted`, `purpose`, `email` y `reference`. Guarda la
  fecha de aceptación (del servidor), la versión de la política de privacidad vigente, la IP y el
  navegador en la tabla `consents`. Devuelve `id`, `accepted_at` y `policy_version`.
- El formulario de contacto llega en la Fase 5 (CRM-01): debe mostrar `ConsentCheckbox`, no enviarse sin
  marcarla, llamar a `recordConsent()` y enviar a HubSpot la fecha `accepted_at`.

## Google Consent Mode v2 (LGPD-04)

Se usa el **modo básico**, que es el que cumple "no ejecutar etiquetas de seguimiento antes del
consentimiento":

1. Al arrancar, antes del CMP, se envía `gtag('consent', 'default', ...)` con todo denegado salvo
   `security_storage`.
2. El CMP envía `gtag('consent', 'update', ...)` cuando el usuario acepta, rechaza o personaliza, y
   también al volver al sitio con una decisión guardada.
3. GTM (`FENIX_GTM_ID`) solo se carga cuando una actualización concede `analytics_storage` o `ad_storage`.
   Si el usuario rechaza, GTM no se carga.

Comprobación en el navegador (DevTools → consola):

```js
dataLayer.filter(e => e[0] === 'consent')                        // default y updates
!!document.querySelector('script[src*="googletagmanager.com"]')   // false hasta aceptar
```

Con GTM cargado, Google Tag Assistant (tagassistant.google.com) muestra en la pestaña **Consent** el
estado por defecto y el estado después de la decisión del usuario.
