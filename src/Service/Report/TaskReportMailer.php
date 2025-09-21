<?php declare(strict_types=1);

namespace App\Service\Report;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class TaskReportMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private string $defaultFromAddress
    ) {
    }

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
    ) : void {
        $fromAddress  = $this->defaultFromAddress ?: 'no-reply@localhost';
        $subjectParts = array_filter([$fromDate, $toDate]);
        $subjectRange = $subjectParts ? ' ' . implode(' - ', $subjectParts) : '';

        $htmlExtra = $summary['total'] === 0 ? '<p><em>Sin tareas en el rango seleccionado.</em></p>' : '';

        $mail = (new Email())
            ->from($fromAddress)
            ->to($to)
            ->subject('Reporte de tareas' . $subjectRange)
            ->text('Adjunto reporte de tareas. Total: ' . $summary['total'])
            ->html('<p>Adjunto reporte de tareas.</p><p>Total: <strong>' . $summary['total'] . '</strong></p>' . $htmlExtra);

        $mail->attach($csv, $csvName, 'text/csv');
        $mail->attach($pdf, $pdfName, 'application/pdf');

        $this->mailer->send($mail);
    }
}
