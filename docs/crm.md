# Formulario, HubSpot y funcionalidades (Fase 5)

## Formulario de contacto y HubSpot (CRM-01)

Flujo de un envío:

1. El formulario (`/contacto` o las páginas por público) envía a `POST /api/leads/{cliente}`:
   nombre, empresa, segmento (fabricante, distribuidor o taller), correo, teléfono, casilla de
   consentimiento y, ocultos, `utm_source`, `utm_medium`, `utm_campaign` y la página de entrada.
2. El backend registra el consentimiento con fecha (tabla `consents`, LGPD-03) y guarda el lead
   (tabla `leads`) asociado a ese consentimiento.
3. Envía el lead al formulario de HubSpot del cliente con la API de envío de formularios
   (`api.hsforms.com/submissions/v3/integration/submit/{portal}/{form}`). No requiere token secreto.
   HubSpot crea el contacto si el correo no existe y lo actualiza si ya existe.
4. Si HubSpot no está configurado o falla, el lead queda guardado con `crm_status` `pendiente` o `error`
   y se reenvía después con:

```bash
php artisan leads:reenviar
```

Protecciones: límite de 5 envíos por minuto por IP y un campo oculto contra robots.

### Configurar HubSpot (una vez por cliente)

1. Crear (o usar) una cuenta de HubSpot. Para el prototipo sirve una cuenta de prueba de Fenix.
2. En **Configuración → Propiedades → Contacto**, crear estas propiedades:

| Propiedad (nombre interno) | Tipo | Valores |
|---|---|---|
| `segmento` | Selección desplegable | `fabricante`, `distribuidor`, `taller` |
| `utm_source` | Texto de una línea | |
| `utm_medium` | Texto de una línea | |
| `utm_campaign` | Texto de una línea | |
| `pagina_de_entrada` | Texto de una línea | |
| `fecha_consentimiento` | Fecha y hora | |

   Nombre, empresa, correo y teléfono son propiedades estándar (`firstname`, `company`, `email`, `phone`).
3. Crear un formulario (**Marketing → Formularios**) que incluya todos esos campos (los de UTM, página de
   entrada y fecha pueden ir ocultos).
4. Copiar el **Portal ID** (número de la cuenta) y el **Form ID** (GUID del formulario, aparece en la URL o en
   "Compartir/Insertar").
5. En el `.env`:

```
FENIX_HUBSPOT_PORTAL_ID=12345678
FENIX_HUBSPOT_FORM_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
```

Opcionales:

- `FENIX_HUBSPOT_HOST=https://api-eu1.hsforms.com` si la cuenta está alojada en la UE.
- `FENIX_HUBSPOT_LEGAL_CONSENT=true` si el formulario tiene activas las opciones de privacidad de datos;
  entonces también se envía el texto de consentimiento aceptado.
- `FENIX_HUBSPOT_FIELD_SEGMENT`, `FENIX_HUBSPOT_FIELD_LANDING`, `FENIX_HUBSPOT_FIELD_CONSENT_DATE` si las
  propiedades se crearon con otros nombres internos.

Luego `php artisan config:clear` y `php artisan leads:reenviar` para enviar lo que haya quedado pendiente.

### Comprobación

- Enviar el formulario con un correo de prueba y ver el contacto en HubSpot con segmento, UTM, página de
  entrada y fecha de consentimiento.
- Enviarlo de nuevo con el mismo correo: HubSpot actualiza el contacto existente, no crea otro.
- Pruebas automáticas: `php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit --filter LeadTest`.

## Página de agradecimiento y conversión (FUNC-02)

Tras un envío correcto el sitio va a `/gracias` (no indexable) y envía a GTM el evento
`generate_lead` con `segment` y `lead_id`, **una sola vez por envío**: al recargar o volver a la página no se
cuenta de nuevo, y una visita directa sin envío no se mide.

## Páginas por público (FUNC-03)

`/fabricantes`, `/distribuidores` y `/talleres`, con su texto, el formulario con el segmento ya elegido y el
evento `view_audience` (`segment`). El texto modelo está en `resources/audiences/default/`; para el
contenido definitivo de un cliente se crea `resources/audiences/{cliente}/{página}.md`. También se sirven
con título, descripción y contenido para buscadores.

## Pie de página (FUNC-04)

Muestra razón social, NIT, dirección, teléfono (`tel:`), correo (`mailto:`), redes sociales, enlace a
Contacto y a las políticas, en todas las páginas. Los datos salen de las variables `FENIX_FOOTER_*`; lo que
no esté configurado no se muestra.

## WhatsApp flotante (FUNC-01)

Botón fijo en todas las páginas con el número de `FENIX_WHATSAPP` (formato internacional, solo dígitos) y un
mensaje según la página (por ejemplo, la referencia del producto). Al hacer clic envía el evento
`click_whatsapp` (`page`).

## Medición y consentimiento

Los eventos (`generate_lead`, `view_audience`, `click_whatsapp`) solo se envían si el usuario aceptó la
analítica en el banner de cookies; sin consentimiento no se registra nada. GTM se carga únicamente después de
ese consentimiento (Fase 3).
