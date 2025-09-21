<?php declare(strict_types=1);

namespace App\Controller;

use App\Exception\ValidationException;
use App\Service\Report\TaskReportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reports')]
class ReportController extends AbstractController
{
    public function __construct(private TaskReportService $taskReportService)
    {
    }

    #[Route('/tasks', name: 'api_reports_tasks', methods: ['GET'])]
    public function tasks(Request $request) : Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $format = strtolower($request->query->get('format', 'csv'));
        if (!in_array($format, ['csv','pdf'], true)) {
            throw new ValidationException([], 'Formato inválido. Use csv o pdf');
        }
        $criteria = [
            'from'       => $request->query->get('from'),
            'to'         => $request->query->get('to'),
            'status'     => $request->query->get('status'),
            'priority'   => $request->query->get('priority'),
            'assignedTo' => $request->query->get('assigned'),
            'sort'       => $request->query->get('sort', 'dueDate'),
            'direction'  => $request->query->get('direction', 'asc'),
        ];
        $tasks    = $this->taskReportService->fetch($criteria);
        $summary  = $this->taskReportService->summarize($tasks);
        $ts       = (new \DateTimeImmutable())->format('Ymd_His');
        $filename = 'tasks_report_' . $ts . '.' . $format;

        if ($format === 'csv') {
            $content = $this->taskReportService->toCsv($tasks, $summary);
            return new Response($content, 200, [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        }
        $pdf = $this->taskReportService->toPdf($tasks, $summary);
        return new Response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }
}
