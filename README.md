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
| Propietario | `demo.admin@nyva.test` |
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

Las pruebas usan SQLite en memoria y no modifican la base de datos `nyva`.

## Base de datos

Las migraciones se pueden ejecutar sobre una base existente: omiten las tablas ya creadas y solo agregan lo que falta. No ejecutes `migrate:fresh`, `migrate:rollback` ni `db:wipe` sobre una base con información que quieras conservar, porque eliminan tablas.
