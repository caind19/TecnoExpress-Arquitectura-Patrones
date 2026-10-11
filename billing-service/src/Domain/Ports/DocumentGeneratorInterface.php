<?php

namespace BillingService\Domain\Ports;

use BillingService\Domain\Comprobante;

// Puerto: contrato para generar el documento del comprobante (hoy PDF).
interface DocumentGeneratorInterface
{
    public function generar(Comprobante $comprobante): string;
}
