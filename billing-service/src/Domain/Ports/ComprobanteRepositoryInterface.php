<?php

namespace BillingService\Domain\Ports;

use BillingService\Domain\Comprobante;

// Puerto: contrato de persistencia de comprobantes emitidos.
interface ComprobanteRepositoryInterface
{
    public function save(Comprobante $comprobante): void;
    public function all(): array;
}
