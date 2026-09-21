# NYVA Laravel

## Descripción

NYVA es un proyecto desarrollado con Laravel orientado a la gestión de establecimientos, planes de suscripción, productos, categorías, pedidos y comunicación con clientes.

Este repositorio contiene la implementación inicial del sistema utilizando Laravel, Eloquent ORM y migraciones para la construcción del esquema de base de datos.

## Tecnologías utilizadas

- Laravel
- PHP
- MariaDB / MySQL
- Eloquent ORM
- Laravel Migrations
- Laravel Seeders

## Implementaciones realizadas

- Configuración inicial del proyecto Laravel.
- Conexión del framework con la base de datos relacional.
- Creación de tablas mediante migraciones.
- Implementación de relaciones entre entidades mediante claves foráneas.
- Configuración de Seeders para la carga de datos iniciales.
- Verificación de ejecución mediante:

```bash
php artisan migrate:fresh --seed
```

## Integrantes

- Jonatan David Arrieta Escobar
- Jesus Alberto Carrillo Jaraba
- Jorge Alberto Daza Hernández 

## Instalación y ejecución

Instalar dependencias del proyecto:

```bash
composer install
```

Configurar el archivo `.env` con los datos de conexión a la base de datos.

Ejecutar migraciones y seeders:

```bash
php artisan migrate:fresh --seed
```

Iniciar el servidor local:

```bash
php artisan serve
```

## Estado del proyecto

Implementación inicial de NYVA mediante Laravel, aplicando migraciones, relaciones entre modelos y Seeders para la configuración inicial de la base de datos.
