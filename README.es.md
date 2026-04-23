# alxarafe/resource-eloquent

![PHP Version](https://img.shields.io/badge/PHP-8.2+-blueviolet?style=flat-square)
![CI](https://github.com/alxarafe/resource-eloquent/actions/workflows/ci.yml/badge.svg)
![Tests](https://github.com/alxarafe/resource-eloquent/actions/workflows/tests.yml/badge.svg)
![Static Analysis](https://img.shields.io/badge/static%20analysis-PHPStan%20%2B%20Psalm-blue?style=flat-square)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](https://github.com/alxarafe/resource-eloquent/issues)

**Adaptador ORM Eloquent para alxarafe/resource-controller.**

Implementa `RepositoryContract`, `QueryContract` y `TransactionContract` usando Illuminate Database.

## Ecosistema

| Paquete | Propósito | Estado |
|---|---|---|
| **[resource-controller](https://github.com/alxarafe/resource-controller)** | Motor CRUD central + componentes UI | ✅ Estable |
| **[resource-eloquent](https://github.com/alxarafe/resource-eloquent)** | Adaptador ORM Eloquent | ✅ Estable |
| **[resource-blade](https://github.com/alxarafe/resource-blade)** | Adaptador de renderizado con Blade | ✅ Estable |
| **[resource-twig](https://github.com/alxarafe/resource-twig)** | Adaptador de renderizado con Twig | ✅ Estable |

## Instalación

```bash
composer require alxarafe/resource-eloquent
```

Esto instalará automáticamente `alxarafe/resource-controller` e `illuminate/database`.

## Uso

```php
use Alxarafe\ResourceController\AbstractResourceController;
use Alxarafe\ResourceController\Contracts\RepositoryContract;
use Alxarafe\ResourceController\Contracts\TransactionContract;
use Alxarafe\ResourceEloquent\EloquentRepository;
use Alxarafe\ResourceEloquent\EloquentTransaction;
use App\Models\Product; // Tu modelo Eloquent

class ProductController extends AbstractResourceController
{
    // ... configuración básica del controlador ...

    protected function getRepository(string $tabId = 'default'): RepositoryContract
    {
        return new EloquentRepository(Product::class);
    }

    protected function getTransaction(): TransactionContract
    {
        // Obtener la conexión DB de Laravel o Capsule
        return new EloquentTransaction(\Illuminate\Support\Facades\DB::connection());
    }
}
```

## Características

- Traduce el filtrado y las consultas declarativas a consultas Eloquent.
- Gestiona la paginación automáticamente.
- Maneja transacciones de base de datos simples y compuestas sin problemas.
- Convierte metadatos de Eloquent directamente en configuraciones de Resource Controller.

## Desarrollo

### Docker

```bash
docker compose up -d
docker exec alxarafe-resources composer install
```

### Ejecutar el pipeline CI en local

```bash
bash bin/ci_local.sh
```

### Ejecutar solo los tests

```bash
bash bin/run_tests.sh
```

## Licencia

GPL-3.0-or-later
