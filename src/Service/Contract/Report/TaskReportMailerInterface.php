<?php declare(strict_types=1);

namespace App\Service\Contract\Report;

interface TaskReportMailerInterface
{
    /**
     * Envía el email con el reporte de tareas adjuntando CSV y PDF.
     *
     * @param string      $to       Destinatario
     * @param array       $summary  Summary con claves: total, byStatus, byPriority
     * @param string      $csv      Contenido CSV
     * @param string      $pdf      Contenido PDF (binario)
     * @param string      $csvName  Nombre de archivo CSV
     * @param string      $pdfName  Nombre de archivo PDF
     * @param string|null $fromDate Fecha desde (YYYY-MM-DD)
     * @param string|null $toDate   Fecha hasta (YYYY-MM-DD)
     */
    public function send(
        string $to,
        array $summary,
        string $csv,
        string $pdf,
        string $csvName,
        string $pdfName,
        ?string $fromDate,
        ?string $toDate
    ): void;
}

