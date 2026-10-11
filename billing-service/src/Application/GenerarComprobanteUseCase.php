<?php

namespace BillingService\Application;

use BillingService\Domain\Comprobante;
use BillingService\Domain\Ports\ComprobanteRepositoryInterface;
use BillingService\Domain\Ports\DocumentGeneratorInterface;

// Caso de uso: Generar comprobante de venta (RF-09), disparado por el
// evento "inventario.descontado" cuando el descuento fue exitoso.
class GenerarComprobanteUseCase
{
    private ComprobanteRepositoryInterface $comprobanteRepository;
    private DocumentGeneratorInterface $documentGenerator;

    public function __construct(ComprobanteRepositoryInterface $comprobanteRepository, DocumentGeneratorInterface $documentGenerator)
    {
        $this->comprobanteRepository = $comprobanteRepository;
        $this->documentGenerator = $documentGenerator;
    }

    public function execute(array $eventoInventario): array
    {
        if (!$eventoInventario['exitoso']) {
            return ['generado' => false, 'motivo' => 'inventario no pudo descontarse'];
        }

        $comprobante = new Comprobante(
            bin2hex(random_bytes(16)),
            $eventoInventario['orderId'],
            $eventoInventario['clienteId'],
            $eventoInventario['items'],
            $eventoInventario['total']
        );

        $this->comprobanteRepository->save($comprobante);
        $rutaPdf = $this->documentGenerator->generar($comprobante);

        return ['generado' => true, 'comprobanteId' => $comprobante->id, 'rutaPdf' => $rutaPdf];
    }
}
