# TecnoExpress SAS — Patrones de Arquitectura (código)

Trabajo de la materia Arquitectura de Software. Implementa cuatro patrones —
Publisher-Subscriber, Inyección de Dependencias, Microservicios y Arquitectura
Hexagonal (Puertos y Adaptadores) — sobre el caso de TecnoExpress SAS, usado
en varias actividades del curso.

Se tomó el flujo de "procesar un pedido" (RF-07 validar inventario, RF-08
descontar inventario, RF-09 generar comprobante PDF) y se dividió en tres
microservicios que se comunican solo a través de un broker de eventos:

```
order-service --publica "pedido.creado"--> BROKER --webhook--> inventory-service
                                               ^                      |
                                               |          publica "inventario.descontado"
                                               +----------webhook-----+---> billing-service (genera PDF)
```

- **broker/** — implementa Publisher-Subscriber (`POST /publish`, `POST /subscribe`).
- **order-service/** — crea el pedido y publica `pedido.creado`.
- **inventory-service/** — suscrito a `pedido.creado`; valida/descuenta stock y publica `inventario.descontado`.
- **billing-service/** — suscrito a `inventario.descontado`; genera el comprobante en PDF.

Cada microservicio está organizado con Arquitectura Hexagonal:

```
src/
  Domain/            entidades y reglas de negocio
    Ports/           interfaces que el dominio necesita
  Application/       casos de uso
  Infrastructure/
    Adapters/        implementaciones concretas de los puertos
    Http/            controladores HTTP
public/index.php      composition root (arma los adaptadores e inyecta
                       las dependencias en los casos de uso -> DI) +
                       front controller
```

Diagrama completo: [`docs/arquitectura_patrones.png`](docs/arquitectura_patrones.png).

## Requisitos

- PHP 8.0 o superior, con la extensión `curl` habilitada (viene activada por
  defecto en XAMPP/Laragon en Windows, y en la mayoría de instalaciones).
- No usa Composer ni ninguna librería externa: todo es PHP puro.

Verificar la instalación:

```bash
php -v
php -m | findstr curl   (en Windows, PowerShell/cmd)
php -m | grep curl      (en Mac/Linux)
```

## Cómo correrlo

Cada servicio se levanta con el servidor embebido de PHP, en su propia
terminal (desde la raíz del repositorio):

```bash
# Terminal 1 — broker (puerto 4000)
php -S localhost:4000 broker/index.php

# Terminal 2 — order-service (puerto 4001)
php -S localhost:4001 -t order-service/public order-service/public/index.php

# Terminal 3 — inventory-service (puerto 4002)
php -S localhost:4002 -t inventory-service/public inventory-service/public/index.php

# Terminal 4 — billing-service (puerto 4003)
php -S localhost:4003 -t billing-service/public billing-service/public/index.php
```

(El segundo `index.php` en cada comando es el "router": hace que el
servidor de PHP mande todas las rutas, como `/orders`, al front
controller, en vez de buscar un archivo con ese nombre.)

Las carpetas `data/` de cada servicio (donde se guarda el estado: pedidos,
stock, comprobantes) se crean solas la primera vez que corre cada servicio.

### Probar el flujo

Con los 4 servicios corriendo, en otra terminal:

```bash
php scripts/demo.php
```

Crea un pedido de prueba y, sin tocar nada más, el evento se propaga por el
broker hasta generar el comprobante en `billing-service/comprobantes/`.

También se puede probar a mano:

```bash
curl -X POST http://localhost:4001/orders ^
  -H "Content-Type: application/json" ^
  -d "{\"clienteId\":\"CLIENTE-TECNOEXPRESS-01\",\"items\":[{\"sku\":\"TEC-001\",\"cantidad\":2,\"precioUnitario\":85000}]}"
```

(la línea de arriba usa `^` para continuar en Windows cmd; en PowerShell o
Mac/Linux se puede escribir todo en una sola línea con `\` en vez de `^`).

SKUs de ejemplo en `inventory-service`: `TEC-001`, `TEC-002`, `TEC-003`.

## Dónde está cada patrón

| Patrón | Dónde está |
|---|---|
| Publisher-Subscriber | `broker/index.php` (`/publish`, `/subscribe`); cada servicio se suscribe la primera vez que recibe una petición (`suscribirseAlBrokerUnaVez()` en cada `public/index.php`) |
| Inyección de Dependencias | Constructores de `CreateOrderUseCase`, `ProcessOrderStockUseCase`, `GenerarComprobanteUseCase`; armado en cada `public/index.php` |
| Microservicios | 3 procesos independientes + broker, cada uno con su propio puerto y datos |
| Arquitectura Hexagonal | `Domain/Ports/` vs. `Infrastructure/Adapters/` en cada servicio |

## Documento de la entrega

La explicación completa (contexto, cada patrón con referencias al código,
capturas y referencias APA) está en `Patrones_Arquitectura_Codigo_TecnoExpress.pdf`,
entregado junto con este repositorio en la plataforma del curso.
