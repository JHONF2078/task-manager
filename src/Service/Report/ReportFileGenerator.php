<?php

namespace App\Service\Report;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Genera archivos (CSV y PDF) para un reporte de tareas y devuelve rutas y contenidos.
 */
class ReportFileGenerator
{
    public function __construct(
        private TaskReportService $taskReportService,
        private string $reportOutputDir
    ) {}

    /**
     * Genera los archivos de reporte para el conjunto de tareas.
     *
     * @param array $tasks   Lista de entidades Task
     * @param array $summary Resumen producido por TaskReportService::summarize
     * @return array{
     *   csvPath:string,
     *   pdfPath:string,
     *   csv:string,
     *   pdf:string,
     *   stamp:string
     * }
     */
    public function generateTaskReportFiles(array $tasks, array $summary): array
    {
        $fs = new Filesystem();
        $dir = rtrim($this->reportOutputDir, '/');
        if (! $fs->exists($dir)) {
            $fs->mkdir($dir, 0775);
        }
        $stamp = date('Ymd_His');

        $csv = $this->taskReportService->toCsv($tasks, $summary);
        $csvPath = $dir . '/tasks_report_' . $stamp . '.csv';
        file_put_contents($csvPath, $csv);

        $pdf = $this->taskReportService->toPdf($tasks, $summary);
        $pdfPath = $dir . '/tasks_report_' . $stamp . '.pdf';
        file_put_contents($pdfPath, $pdf);

        return [
            'csvPath' => $csvPath,
            'pdfPath' => $pdfPath,
            'csv' => $csv,
            'pdf' => $pdf,
            'stamp' => $stamp,
        ];
    }
}

