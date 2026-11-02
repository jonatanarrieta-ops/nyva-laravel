# NYVA: estado final

## Funcionalidades terminadas

- **Plataforma:** dashboard, establecimientos con propietario, activación y suspensión, usuarios, planes con límites y funcionalidades, suscripciones y estadísticas.
- **Establecimiento:** dashboard, datos del negocio, logotipo, portada, enlace público, estado de atención, horarios, categorías, productos con imágenes, pedidos, reportes, preguntas frecuentes, equipo con permisos y configuración del chatbot.
- **Página pública:** URL por nombre, información real, catálogo, carrito, checkout sin cuenta y chatbot.
- **Chatbot:** consultas con datos reales, pedido conversacional, resumen, confirmación explícita, recuperación de conversaciones e historial.
- **Pedidos:** precios del servidor, transacción, prevención de duplicados, estados controlados y enlace público de solo lectura.
- **Notificaciones internas:** pedido nuevo, confirmación, cambio de estado y alertas administrativas.

## Funcionalidades pendientes

- Vista de tarjetas en móvil para las tablas. Hoy se desplazan horizontalmente dentro de su contenedor, sin desbordar la página.
- Imágenes adicionales por establecimiento: hay logotipo y portada, no galería.
- Conexión real con un proveedor de IA, que requiere una clave.
- Pagos en línea, fuera del alcance.
- Página de la cuenta en español: algunos títulos de configuración provienen de la plantilla.

## Pruebas ejecutadas

| Herramienta | Resultado |
|---|---|
| Pest | 192 aprobadas y 1 omitida |
| Laravel Pint | Sin observaciones |
| PHPStan nivel 7 | Sin errores |
| `npm run build` | Sin errores |
| Instalación limpia en SQLite temporal | Migraciones y `DemoSeeder` correctos |
| Migración sobre `nyva` | Sin pérdida de datos: 10 pedidos y 21 líneas antes y después |

En navegador real se verificó:

- Pedido completo como visitante por chat en móvil, incluida la recuperación tras recargar y el seguimiento público.
- Llegada del pedido y de la notificación al panel del propietario.
- Bloqueo de rutas por rol.
- Panel de plataforma en tableta y escritorio.
- Ausencia de desbordes de página en móvil y tableta, y consola sin errores.

## Problemas encontrados

- Las migraciones no coincidían con la base real. Quedaron conciliadas, incluida la tabla `conversations`.
- Una sesión abierta conservaba el acceso tras desactivar la cuenta. Ahora se bloquea.
- La suscripción del establecimiento original `nyva-demo-restaurant` venció el 18/10/2026, así que no recibe pedidos hasta renovarla desde `/admin/subscriptions`.
- El navegador puede conservar una copia antigua de `livewire.js`. Si un botón no responde, recarga con `Ctrl+F5`.

## Decisiones y suposiciones

- Una sola política, `EstablishmentPolicy`, concentra la autorización, porque todos los recursos dependen del establecimiento.
- El modo oscuro de las pantallas heredadas se resuelve con reglas CSS compartidas, y la página pública conserva su tema claro.
- Eliminar un establecimiento no está disponible: la plataforma lo suspende o desactiva, para conservar pedidos e historial.
- Los pedidos registrados desde el panel no dependen del horario público.

## Configuraciones externas

- Clave de un proveedor de IA, opcional: ver `README.md`.
- Tarea programada `php artisan schedule:run` para los avisos de suscripciones.
- Correo real si se quiere usar la recuperación de contraseña fuera del registro local.

## Ejecución

Sigue `README.md`. En una base existente ejecuta `php artisan migrate`, `php artisan storage:link`, `npm run build` y `php artisan db:seed --class=DemoSeeder`.
