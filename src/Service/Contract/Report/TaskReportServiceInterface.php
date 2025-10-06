<?php

declare(strict_types=1);

namespace App\Service\Contract\Report;

/**
 * Interfaz para el servicio de generación de reportes de tareas
 */
interface TaskReportServiceInterface
{
    /**
     * Obtiene las tareas según los criterios de filtrado.
     * @param array $criteria
     * @return array
     */
    public function fetch(array $criteria): array;

    /**
     * Genera un resumen de las tareas.
     * @param array $tasks
     * @return array{total: int, byStatus: array, byPriority: array}
     */
    public function summarize(array $tasks): array;

    /**
     * Convierte las tareas a formato CSV.
     * @param array $tasks
     * @param array $summary
     * @return string
     */
    public function toCsv(array $tasks, array $summary): string;

    /**
     * Convierte las tareas a formato PDF.
     * @param array $tasks
     * @param array $summary
     * @return string
     */
    public function toPdf(array $tasks, array $summary): string;
}
