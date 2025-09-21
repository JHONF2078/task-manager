<?php declare(strict_types=1);

namespace App\Service\Report;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment; // agregado

/**
 * Servicio para generar reportes de tareas en CSV y PDF con filtros.
 */
class TaskReportService
{
    private const MAX_REPORT_ROWS = 10000; // límite de seguridad para no saturar memoria

    public function __construct(private TaskRepository $tasks, private Environment $twig)
    {
    }

    /**
     * @param array $criteria keys: from, to (YYYY-MM-DD), status, priority, assignedTo
     *
     * @return Task[]
     */
    public function fetch(array $criteria) : array
    {
        $filters = [];
        if (!empty($criteria['status'])) {
            $filters['status'] = $criteria['status'];
        }
        if (!empty($criteria['priority'])) {
            $filters['priority'] = $criteria['priority'];
        }
        if (!empty($criteria['assignedTo'])) {
            $filters['assignedTo'] = $criteria['assignedTo'];
        }
        if (!empty($criteria['from'])) {
            $filters['dueFrom'] = $criteria['from'];
        }
        if (!empty($criteria['to'])) {
            $filters['dueTo'] = $criteria['to'];
        }

        $result = $this->tasks->search($filters, 1, self::MAX_REPORT_ROWS, $criteria['sort'] ?? 'dueDate', $criteria['direction'] ?? 'asc');
        /** @var Task[] $data */
        $data = $result['data'];
        return $data;
    }

    public function summarize(array $tasks) : array
    {
        $total      = count($tasks);
        $byStatus   = [];
        $byPriority = [];
        foreach ($tasks as $t) {
            $byStatus[$t->getStatus()]     = ($byStatus[$t->getStatus()] ?? 0)     + 1;
            $byPriority[$t->getPriority()] = ($byPriority[$t->getPriority()] ?? 0) + 1;
        }
        ksort($byStatus);
        ksort($byPriority);
        return [ 'total' => $total, 'byStatus' => $byStatus, 'byPriority' => $byPriority ];
    }

    public function toCsv(array $tasks, array $summary) : string
    {
        // Generación estándar usando fputcsv con delimitador ';' para mantener compatibilidad anterior.
        $fp    = fopen('php://temp', 'r+');
        $write = function (array $row) use ($fp) { fputcsv($fp, $row, ';'); };

        $write(['Reporte de Tareas']);
        $write(['Total', $summary['total']]);
        $write([]);
        $write(['ID', 'Titulo', 'Estado', 'Prioridad', 'Vencimiento', 'Asignado']);
        foreach ($tasks as $t) {
            $write([
                $t->getId(),
                $t->getTitle(),
                $t->getStatus(),
                $t->getPriority(),
                $t->getDueDate()?->format('Y-m-d'),
                $t->getAssignedTo()?->getEmail() ?? ''
            ]);
        }
        $write([]);
        $write(['Resumen por estado']);
        foreach ($summary['byStatus'] as $k => $v) {
            $write([$k, $v]);
        }
        $write([]);
        $write(['Resumen por prioridad']);
        foreach ($summary['byPriority'] as $k => $v) {
            $write([$k, $v]);
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);
        return $csv;
    }

    public function toPdf(array $tasks, array $summary) : string
    {
        $html    = $this->buildHtml($tasks, $summary);
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }

    private function buildHtml(array $tasks, array $summary) : string
    {
        // Renderizado mediante Twig (plantilla reutilizable)
        return $this->twig->render('report/tasks_report.html.twig', [
            'tasks'   => $tasks,
            'summary' => $summary,
        ]);
    }

    public function buildHtmlForMail(array $tasks, array $summary) : string
    {
        return $this->buildHtml($tasks, $summary);
    }
}
