# NYVA

Asistente virtual para la automatización de atención al cliente y gestión de pedidos. Es una plataforma web SaaS multiestablecimiento orientada a restaurantes, hoteles y cocinas ocultas.

Tiene tres módulos conectados:

- **Administración de NYVA:** establecimientos, usuarios, planes, suscripciones y estadísticas.
- **Panel del establecimiento:** negocio, catálogo, horarios, pedidos, equipo y chatbot.
- **Página pública:** catálogo, carrito, chatbot y seguimiento del pedido, sin iniciar sesión.

## Tecnologías

Laravel 13, PHP 8.3 o superior, Livewire, Volt, Flux, Fortify, MariaDB/MySQL, Tailwind CSS, Vite, Pest, Pint y PHPStan.

## Instalación

Requisitos: PHP 8.3 o superior, Composer, Node/NPM y MariaDB/MySQL (por ejemplo, con XAMPP).

```bash
composer install
```

```bash
npm install
```

Copia `.env.example` como `.env`, genera la clave y crea la base de datos `nyva`:

```bash
php artisan key:generate
```

```
DB_CONNECTION=mariadb
DB_DATABASE=nyva
DB_USERNAME=root
DB_PASSWORD=
```

Ejecuta las migraciones, compila y arranca:

```bash
php artisan migrate
```

```bash
php artisan storage:link
```

```bash
npm run build
```

```bash
php artisan serve
```

La aplicación queda disponible en `http://127.0.0.1:8000`.

Para los avisos de suscripciones por vencer, programa `php artisan schedule:run` cada minuto o ejecuta `php artisan nyva:check-subscriptions`.

## Datos de demostración

```bash
php artisan db:seed --class=DemoSeeder
```

Se puede ejecutar varias veces y no modifica otra información. Las cuentas son solo para uso local, con contraseña `password`:

| Perfil | Correo |
|---|---|
| Administrador de plataforma | `demo.plataforma@nyva.test` |
| Propietario de Hamburguesas La Esquina | `demo.admin@nyva.test` |
| Propietario de Café Aurora | `demo.cafe@nyva.test` |
| Usuario autorizado (pedidos) | `demo.personal@nyva.test` |
| Cliente con cuenta | `demo.cliente@nyva.test` |

## Roles

| Rol | Acceso |
|---|---|
| `platform_admin` | Panel `/admin`, con información global. No opera los negocios |
| `establishment_admin` | Su panel, con toda la información de sus establecimientos |
| `establishment_staff` | Las secciones permitidas: catálogo, pedidos o configuración |
| `customer` | Mis pedidos, para quien cree una cuenta |
| Visitante | Página pública, sin iniciar sesión |

El registro público crea clientes. La plataforma crea los establecimientos y sus propietarios.

## Reglas principales

- Un establecimiento recibe pedidos si está activo y abierto, tiene suscripción vigente con pedidos en línea y está dentro de su horario. Sin horarios configurados se considera abierto.
- Los planes definen duración, límites de productos y usuarios, y funcionalidades: chatbot, reportes, pedidos en línea y usuarios autorizados. Los límites se aplican en el servidor.
- Precios, subtotales y totales siempre se calculan en el servidor, dentro de una transacción.
- Los estados de pedido avanzan sin saltos: `pending`, `confirmed`, `preparing`, `shipped`, `delivered`. Se puede pasar a `cancelled` antes del envío.
- El enlace `/pedido/{token}` es solo de consulta y oculta parcialmente los datos personales.

## Arquitectura

La navegación sigue el patrón rutas, controladores y vistas Blade. Livewire se conserva en las pantallas de cuenta, reportes y en los componentes interactivos.

### Rutas

Todas están en `routes/web.php`, tienen nombre y se agrupan por entidad. Para verlas: `php artisan route:list --except-vendor`.

| Grupo | Declaración | Middleware |
|---|---|---|
| Página pública | `Route::controller(CatalogController::class)`, `Route::prefix('carrito')->name('cart.')`, `checkout` y `pedido/{token}` | Ninguno. El seguimiento usa `throttle` y `tracking` |
| Catálogo del negocio | `Route::resource('categories')` y `Route::resource('products')` | `auth`, `establishment:catalog` |
| Pedidos del negocio | `Route::resource('orders')->only(['index', 'create', 'store', 'show', 'update'])` | `auth`, `establishment:orders` |
| Configuración del negocio | `Route::prefix('schedules')` y `Route::resource('faqs')` | `auth`, `establishment:settings` |
| Plataforma | `Route::prefix('admin')->name('admin.')` con `Route::resource` de `establishments`, `users`, `plans` y `subscriptions` | `auth`, `platform` |

Los verbos siguen su uso habitual: `GET` para consultar, `POST` para crear, `PUT`/`PATCH` para actualizar y `DELETE` para eliminar. Activar, suspender o desactivar establecimientos y usuarios se hace con `PATCH .../status`, porque no se eliminan.

### Controladores

En `app/Http/Controllers`. Los de la plataforma están en `Admin/`.

| Controlador | Tipo | Responsabilidad |
|---|---|---|
| `CatalogController` | Estándar | Portada y página pública de cada negocio |
| `CartController` | Estándar | Carrito en sesión: listar, agregar, cambiar cantidad, quitar y vaciar |
| `CheckoutController` | Estándar | Datos de entrega y creación del pedido |
| `TrackingController` | Estándar | Seguimiento público del pedido |
| `ScheduleController` | Estándar | Horario semanal del negocio |
| `CategoryController`, `ProductController`, `FaqController` | Resource | Catálogo y preguntas frecuentes del negocio |
| `OrderController` | Resource | Pedidos del panel y cambio de estado |
| `Admin\EstablishmentController`, `Admin\UserController` | Resource | Negocios, propietarios, cuentas y roles |
| `Admin\PlanController`, `Admin\SubscriptionController` | Resource | Planes y suscripciones |
| `DashboardController` | Invocable | Inicio del panel según el rol |

Los controladores consultan los registros dentro de los negocios del usuario autenticado, así que un registro ajeno responde 404. La lógica de negocio está en `app/Actions` (`PlaceOrder`, `UpdateOrderStatus`), `app/Services` (`Cart`, `PlanLimits`, `Chatbot`) y `app/Policies/EstablishmentPolicy.php`.

### Vistas

- Dos layouts maestros con `@yield('content')`: `layouts/public.blade.php` para la página pública y `layouts/app.blade.php` para los paneles.
- Una carpeta por entidad con `index`, `create`, `edit` y un `_form` compartido. Por ejemplo, `resources/views/categories/`. Las vistas públicas están en `resources/views/public/` y las de la plataforma en `resources/views/admin/`.
- Componentes Blade en `resources/views/components/`: `x-ui.*`, `x-page-header` y `x-confirm-action`, que pide confirmación antes de eliminar.
- Estilos con Tailwind CSS. Después de cambiar una vista ejecuta `npm run build`, o deja corriendo `npm run dev` mientras trabajas.

### Livewire

Las páginas Livewire están en `resources/views/pages/`: inicio de sesión y registro (Fortify), configuración de la cuenta, dashboard y estadísticas de la plataforma, Mi establecimiento, reportes, configuración del chatbot, equipo y Mis pedidos. También son Livewire el contador del carrito, la campana de notificaciones y el widget del chatbot.

## Chatbot

Funciona sin servicios externos: responde con horarios, productos, precios, disponibilidad, categorías, ubicación y preguntas frecuentes registradas por el establecimiento. Si no tiene la información, lo indica.

Puede agregar productos, modificar cantidades, eliminarlos, cancelar y retomar un pedido interrumpido hasta por 24 horas. Solo registra el pedido cuando el cliente lo confirma de forma explícita tras ver el resumen.

Para que un proveedor de IA redacte las respuestas informativas, configura en `.env`:

```
CHATBOT_AI_DRIVER=anthropic
CHATBOT_AI_KEY=tu_clave
CHATBOT_AI_MODEL=modelo_del_proveedor
```

Los valores admitidos de `CHATBOT_AI_DRIVER` son `anthropic` y `openai`. `CHATBOT_AI_URL` es opcional. El proveedor nunca decide precios ni confirma pedidos. Si falla, se usa la respuesta estructurada.

## Pruebas

```bash
php artisan test
```

```bash
vendor/bin/pint --test
```

```bash
vendor/bin/phpstan analyse
```

Para ejecutar las tres revisiones como lo hace GitHub Actions en cada pull request y en cada push a `main`:

```bash
composer ci:check
```

Las pruebas usan SQLite en memoria y no modifican la base de datos `nyva`.

## Base de datos

Las migraciones se pueden ejecutar sobre una base existente: omiten las tablas ya creadas y solo agregan lo que falta. No ejecutes `migrate:fresh`, `migrate:rollback` ni `db:wipe` sobre una base con información que quieras conservar, porque eliminan tablas.

Si tu base local se creó con una versión anterior de las migraciones y `php artisan migrate` falla por el tipo de una columna, recréala. Esto borra los datos locales:

```bash
php artisan migrate:fresh
```

```bash
php artisan db:seed --class=DemoSeeder
```
