<?php declare(strict_types=1);

namespace App\Command;

use App\Service\Report\ReportFileGenerator;
use App\Service\Report\TaskReportMailer;
use App\Service\Report\TaskReportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:tasks:daily-report', description: 'Genera un reporte (por defecto diario) de tareas en CSV y PDF, opcionalmente lo envía por email')]
class DailyTaskReportCommand extends Command
{
    public function __construct(
        private TaskReportService $reportService,
        private TaskReportMailer $reportMailer,
        private ReportFileGenerator $reportFileGenerator
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->addOption('from', null, InputOption::VALUE_OPTIONAL, 'Fecha desde (YYYY-MM-DD)')
            ->addOption('to', null, InputOption::VALUE_OPTIONAL, 'Fecha hasta (YYYY-MM-DD)')
            ->addOption('status', null, InputOption::VALUE_OPTIONAL, 'Estado de tarea')
            ->addOption('priority', null, InputOption::VALUE_OPTIONAL, 'Prioridad')
            ->addOption('assigned', null, InputOption::VALUE_OPTIONAL, 'ID usuario asignado')
            ->addOption('sort', null, InputOption::VALUE_OPTIONAL, 'Campo orden (dueDate|priority|status)', 'dueDate')
            ->addOption('direction', null, InputOption::VALUE_OPTIONAL, 'asc|desc', 'asc')
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Enviar a este email (adjunta CSV y PDF)');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $from = $input->getOption('from');
        $to   = $input->getOption('to');
        if (! $from && ! $to) {
            $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
            $from  = $today;
            $to    = $today; // reporte diario por defecto
        }
        $criteria = [
            'from'       => $from,
            'to'         => $to,
            'status'     => $input->getOption('status'),
            'priority'   => $input->getOption('priority'),
            'assignedTo' => $input->getOption('assigned'),
            'sort'       => $input->getOption('sort'),
            'direction'  => $input->getOption('direction'),
        ];

        $output->writeln('<info>Generando reporte...</info>');
        $tasks   = $this->reportService->fetch($criteria);
        $summary = $this->reportService->summarize($tasks);

        // Generar archivos mediante el generador desacoplado
        $files   = $this->reportFileGenerator->generateTaskReportFiles($tasks, $summary);
        $csvPath = $files['csvPath'];
        $pdfPath = $files['pdfPath'];
        $csv     = $files['csv'];
        $pdf     = $files['pdf'];

        $output->writeln("<comment>Archivos:</comment>\n - $csvPath\n - $pdfPath");
        $output->writeln('Total tareas: ' . $summary['total']);

        $emailTo = $input->getOption('email');
        if ($emailTo) {
            $output->writeln('<info>Enviando email a ' . $emailTo . '...</info>');
            try {
                $this->reportMailer->send(
                    $emailTo,
                    $summary,
                    $csv,
                    $pdf,
                    basename($csvPath),
                    basename($pdfPath),
                    $from,
                    $to
                );
                $output->writeln('<info>Email enviado.</info>');
            } catch (\Throwable $e) {
                $output->writeln('<error>Error enviando email: ' . $e->getMessage() . '</error>');
            }
        }
        return Command::SUCCESS;
    }
}
