# NYVA: auditoría técnica

Auditoría sobre la rama `frontend`, commit `4df8bfb`.

## Estado actual

- Laravel 13, PHP 8.5, Livewire 4, Volt, Flux, Fortify, MariaDB, Vite, Pest, Pint y PHPStan.
- 116 pruebas: 115 aprobadas y 1 omitida. Pint pasa. PHPStan reporta 51 avisos, casi todos por tipos genéricos en los modelos.
- No hay integración de IA ni dependencias para ello.

## Arquitectura identificada

- Instalación única con datos separados por `establishments.user_id`.
- Interfaz en componentes Livewire de un solo archivo, en `resources/views/pages/`.
- Lógica de dominio en `app/Actions/PlaceOrder.php` y `app/Services/Cart.php`.
- Acceso por el middleware `admin`, sin policies.
- La ruta `dashboard` es un closure en `routes/web.php`.

## Funcionalidades existentes

| Área | Estado |
|---|---|
| Autenticación con Fortify | Completa |
| Establecimientos, categorías y productos | Completa para el dueño |
| Pedidos, estados, dashboard y reportes | Completa |
| Catálogo, carrito y checkout | Completa, con checkout que exige cuenta |
| Mis pedidos y seguimiento | Completa para clientes con cuenta |

## Funcionalidades pendientes

- Panel de plataforma: usuarios, planes, suscripciones y estadísticas globales.
- Gestión de horarios y preguntas frecuentes. Las tablas existen, pero nada las usa.
- Chatbot e historial de conversaciones.
- URL pública por nombre, logotipo y estado abierto o cerrado.
- Usuarios autorizados por establecimiento.
- Pedidos sin cuenta, límites de plan y notificaciones.

## Problemas encontrados

1. `platform_admin` se comporta como un administrador de establecimiento.
2. Cualquier establecimiento recibe pedidos sin importar su horario o suscripción.
3. Una cuenta inactiva puede iniciar sesión.
4. `role` y `status` están en la lista de asignación masiva de `User`.
5. `conversations` no tiene dónde guardar mensajes.
6. Los modelos usan `HasFactory` sin que existan fábricas.
7. Parte de las vistas no tiene estilos para modo oscuro.

## Riesgos técnicos

- La base `nyva` conserva un historial de migraciones distinto. Las migraciones nuevas deben ser incrementales, y `migrate:rollback` eliminaría tablas.
- Los valores de `status` son texto en la base real.
- Un proveedor de IA externo no debe decidir precios ni confirmar pedidos.
- El navegador puede guardar una copia antigua de `livewire.js`.

## Plan priorizado

1. Datos multi-tenant, roles y acceso.
2. Panel de plataforma, planes y suscripciones.
3. Panel del establecimiento: horarios, preguntas frecuentes, equipo y chatbot.
4. Página pública, chatbot, pedidos sin cuenta y notificaciones.
5. Diseño, seguridad, pruebas y documentación.

## Suposiciones

- El registro público crea clientes. Los negocios y propietarios los crea la plataforma.
- Sin horarios configurados, un establecimiento se considera abierto.
- Las suscripciones se administran manualmente, sin pagos externos.
- Un plan sin límite usa `null` en `product_limit` y `user_limit`.
