<?php

namespace BillingService\Infrastructure\Adapters;

use BillingService\Domain\Comprobante;
use BillingService\Domain\Ports\DocumentGeneratorInterface;

// Adaptador de salida: escribe un PDF válido a mano, sin librerías
// externas (ni Composer), siguiendo la estructura mínima de un PDF
// (objetos, tabla xref y trailer). Es un adaptador reemplazable: si
// luego se quiere usar una librería como FPDF, solo se cambia esta
// clase, sin tocar el caso de uso ni el dominio.
class SimplePdfDocumentGenerator implements DocumentGeneratorInterface
{
    private string $outputDir;

    public function __construct(string $outputDir)
    {
        $this->outputDir = $outputDir;
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }
    }

    public function generar(Comprobante $comprobante): string
    {
        $rutaPdf = $this->outputDir . "/comprobante-{$comprobante->id}.pdf";
        file_put_contents($rutaPdf, $this->construirPdf($comprobante));
        return $rutaPdf;
    }

    private function escape(string $texto): string
    {
        // El PDF usa WinAnsiEncoding (un byte por carácter); los .php
        // están en UTF-8, así que hay que convertir antes de escribir
        // o las tildes salen mal ("ó" -> "Ã³").
        $texto = mb_convert_encoding($texto, 'Windows-1252', 'UTF-8');
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $texto);
    }

    private function construirPdf(Comprobante $comprobante): string
    {
        $lineas = [
            ['texto' => 'TecnoExpress SAS', 'tam' => 18],
            ['texto' => 'Comprobante de venta', 'tam' => 12],
            ['texto' => '', 'tam' => 10],
            ['texto' => "Comprobante: {$comprobante->id}", 'tam' => 10],
            ['texto' => "Pedido: {$comprobante->orderId}", 'tam' => 10],
            ['texto' => "Cliente: {$comprobante->clienteId}", 'tam' => 10],
            ['texto' => "Fecha emisión: {$comprobante->emitidoEn}", 'tam' => 10],
            ['texto' => '', 'tam' => 10],
            ['texto' => 'Items:', 'tam' => 11],
        ];
        foreach ($comprobante->items as $it) {
            $lineas[] = ['texto' => "- {$it['sku']}  x{$it['cantidad']}  @ \${$it['precioUnitario']}", 'tam' => 10];
        }
        $lineas[] = ['texto' => '', 'tam' => 10];
        $lineas[] = ['texto' => "Total: \${$comprobante->total}", 'tam' => 12];

        // --- content stream (operadores de texto PDF) ---
        $contentOps = "BT\n";
        foreach ($lineas as $i => $linea) {
            $contentOps .= "/F1 {$linea['tam']} Tf\n";
            $contentOps .= $i === 0 ? "72 740 Td\n" : "0 -18 Td\n";
            $contentOps .= '(' . $this->escape($linea['texto']) . ") Tj\n";
        }
        $contentOps .= 'ET';

        $objetos = [];
        $objetos[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objetos[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objetos[] = '<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R >> >> '
            . '/MediaBox [0 0 612 792] /Contents 5 0 R >>';
        $objetos[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $largo = strlen($contentOps);
        $objetos[] = "<< /Length $largo >>\nstream\n$contentOps\nendstream";

        // --- ensamblado del archivo con cálculo de offsets para xref ---
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objetos as $i => $obj) {
            $offsets[] = strlen($pdf);
            $num = $i + 1;
            $pdf .= "$num 0 obj\n$obj\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $n = count($objetos) + 1;
        $pdf .= "xref\n0 $n\n0000000000 65535 f \n";
        for ($i = 1; $i < $n; $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $pdf .= "trailer\n<< /Size $n /Root 1 0 R >>\nstartxref\n$xrefOffset\n%%EOF";

        return $pdf;
    }
}
