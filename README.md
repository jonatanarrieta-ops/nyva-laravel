# NYVA

Plataforma web para la gestión de establecimientos, productos y pedidos. Los administradores gestionan su operación y consultan indicadores; los clientes consultan catálogos, realizan pedidos y hacen seguimiento.

## Tecnologías

- Laravel, PHP, Livewire y Volt
- MariaDB/MySQL
- HTML, CSS (Tailwind) y JavaScript
- Node/NPM

## Instalación

Requisitos: PHP 8.3 o superior, Composer, Node/NPM y MariaDB/MySQL (por ejemplo, con XAMPP).

```bash
composer install
npm install
```

Copia `.env.example` como `.env`, genera la clave y configura la base de datos:

```bash
php artisan key:generate
```

```
DB_CONNECTION=mariadb
DB_DATABASE=nyva
DB_USERNAME=root
DB_PASSWORD=
```

Crea la base de datos `nyva` y ejecuta:

```bash
php artisan migrate
php artisan storage:link
npm run build
php artisan serve
```

La aplicación queda disponible en `http://127.0.0.1:8000`.

### Datos de demostración

```bash
php artisan db:seed --class=DemoSeeder
```

Crea dos establecimientos ficticios, su catálogo y pedidos en todos los estados, sin modificar la información existente. Las cuentas son `demo.admin@nyva.test` y `demo.cliente@nyva.test`, con contraseña `password`. Son solo para uso local.

## Funcionalidades

### Administrador

- **Establecimientos:** crear, editar, eliminar y activar los establecimientos propios.
- **Categorías:** organizar los productos de cada establecimiento.
- **Productos:** precio, disponibilidad, estado e imagen (JPG, PNG o WEBP, máximo 2 MB).
- **Pedidos:** registrar, filtrar y consultar el detalle de los pedidos.
- **Estados:** avance controlado, sin saltos.
- **Dashboard:** indicadores, pedidos recientes y resumen por estado.
- **Reportes:** ventas, unidades, productos más vendidos y establecimientos por periodo. Los pedidos cancelados no cuentan como ventas.

```
pending → confirmed → preparing → shipped → delivered
pending, confirmed, preparing → cancelled
```

### Cliente

- **Catálogo:** establecimientos y productos por categoría, sin iniciar sesión.
- **Carrito:** agregar, cambiar cantidades, eliminar y vaciar. Un carrito pertenece a un solo establecimiento.
- **Checkout:** revisión del pedido y datos de entrega. Requiere iniciar sesión.
- **Pedidos:** historial y detalle de los pedidos propios.
- **Seguimiento:** avance visual del estado del pedido.

## Seguridad

- Los administradores solo acceden a la información de sus establecimientos y los clientes solo a sus pedidos.
- Las cuentas registradas desde la aplicación tienen rol `customer`. Los roles `admin`, `platform_admin` y `establishment_admin` acceden al panel.
- Precios, subtotales y totales se calculan en el servidor con los datos de la base de datos.
- El pedido y sus productos se guardan en una transacción.

## Pruebas

```bash
php artisan test
```

Las pruebas usan SQLite en memoria y no modifican la base de datos `nyva`.

## Base de datos

Las migraciones se pueden ejecutar sobre una base existente: omiten las tablas ya creadas y solo agregan lo que falta. No ejecutes `migrate:fresh`, `migrate:rollback` ni `db:wipe` sobre una base con información que quieras conservar, porque eliminan tablas.
