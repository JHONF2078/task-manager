<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Servicio responsable de enviar el correo de recuperación de contraseña.
 * Tolera la ausencia de un MAILER_DSN real: en ese caso loguea y no lanza excepción.
 */
class ResetPasswordMailService
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private ?string $fromAddress = null,
        private ?string $fromName = null,
    ) {
        // Valores por defecto si no se configuraron en services.yaml / parámetros.
        $this->fromAddress ??= 'no-reply@example.local';
        $this->fromName    ??= 'Soporte';
    }

    /**
     * Envía el email de recuperación.
     * Si falla el envío se registra el error pero NO se propaga (para no filtrar existencia del email al usuario final).
     */
    public function send(User $user, string $token, string $resetUrl) : void
    {
        //generar valor aleatorio y unico para el correo
        $entropy = bin2hex(random_bytes(4));
        //Recupera tu contraseña - ab12cd - 2025-09-27 20:15
        $subject = 'Recupera tu contraseña - ' . substr($token, 0, 6) . ' - ' . date('Y-m-d H:i');
        // ID crudo sin brackets (addIdHeader los añade). Formato recomendado: uniqueid@domain
        $messageIdRaw = time() . '.' . bin2hex(random_bytes(6)) . '@miapp.local';
        $this->logger->info('[RESET_MAIL_START] Inicio', [ 'to' => $user->getEmail(), 'url' => $resetUrl, 'subject' => $subject, 'entropy' => $entropy, 'message_id' => '<' . $messageIdRaw . '>' ]);
        try {
            // Usamos plantilla Twig (HTML + texto). Si hubiera problema con Twig, fallback a texto plano.
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromAddress, $this->fromName))
                ->to(new Address($user->getEmail(), $user->getName() ?: $user->getEmail()))
                ->subject($subject)
                ->htmlTemplate('emails/reset_password.html.twig')
                ->textTemplate('emails/reset_password.txt.twig')
                ->context([
                    'user'     => $user,
                    'token'    => $token,
                    'resetUrl' => $resetUrl,
                    'entropy'  => $entropy,
                ]);
            // Headers diferenciadores para evitar colapso/dedupe de Gmail
            $headers = $email->getHeaders();
            $headers->addIdHeader('Message-ID', $messageIdRaw);
            $headers->addTextHeader('X-Source-App', 'MiApp');
            $headers->addTextHeader('X-Entropy', $entropy);
            $headers->addTextHeader('List-Unsubscribe', '<mailto:' . $this->fromAddress . '>');

            $this->mailer->send($email);
            $this->logger->info('[RESET_MAIL_OK] Enviado con plantilla', [ 'to' => $user->getEmail(), 'message_id' => '<' . $messageIdRaw . '>' ]);
        } catch (\Throwable $e) {
            $this->logger->warning('[RESET_MAIL_FALLBACK] Plantilla falló', [ 'error' => $e->getMessage() ]);
            // Fallback: intentar un correo simple sin plantilla si fallo fue por Twig
            try {
                $fallbackSubject = $subject . ' (TXT)';
                $fallback        = (new Email())
                    ->from(new Address($this->fromAddress, $this->fromName))
                    ->to($user->getEmail())
                    ->subject($fallbackSubject)
                    ->text("Hola,\n\nEnlace para restablecer tu contraseña (token parcial: " . substr($token, 0, 6) . " / entropía: $entropy ):\n$resetUrl\n\nSi no solicitaste esto, ignora este correo.\n");
                $fh = $fallback->getHeaders();
                $fh->addIdHeader('Message-ID', $messageIdRaw);
                $fh->addTextHeader('X-Source-App', 'MiApp');
                $fh->addTextHeader('X-Entropy', $entropy);
                $fh->addTextHeader('List-Unsubscribe', '<mailto:' . $this->fromAddress . '>');

                $this->mailer->send($fallback);
                $this->logger->info('[RESET_MAIL_OK_FALLBACK] Enviado texto plano', [ 'to' => $user->getEmail(), 'message_id' => '<' . $messageIdRaw . '>' ]);
            } catch (\Throwable $inner) {
                $this->logger->error('[RESET_MAIL_FAIL] No se pudo enviar', [
                    'original_error' => $e->getMessage(),
                    'fallback_error' => $inner->getMessage(),
                    'to'             => $user->getEmail(),
                    'message_id'     => '<' . $messageIdRaw . '>' ,
                ]);
            }
        }
    }
}
