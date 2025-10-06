<?php

declare(strict_types=1);

namespace App\Service\Contract\Report;

/**
 * Interfaz para el servicio de generación de archivos de reporte
 */
interface ReportFileGeneratorInterface
{
    /**
     * Genera los archivos de reporte para el conjunto de tareas.
     * @param array $tasks
     * @param array $summary
     * @return array{csvPath: string, pdfPath: string, csv: string, pdf: string, stamp: string}
     */
    public function generateTaskReportFiles(array $tasks, array $summary): array;
}

