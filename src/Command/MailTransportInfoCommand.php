<?php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use ReflectionClass;

#[AsCommand(name: 'app:mail:transport-info', description: 'Muestra información del transporte de correo efectivo (clase y DSN si es posible).')]
class MailTransportInfoCommand extends Command
{
    public function __construct(private MailerInterface $mailer) { parent::__construct(); }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Diagnóstico de transporte de correo');

        // El MailerInterface envuelve un transporte; accedemos a la propiedad privada mediante reflexión.
        $refMailer = new ReflectionClass($this->mailer);
        $transportProp = null;
        foreach (['transport','transports'] as $propName) {
            if ($refMailer->hasProperty($propName)) { $transportProp = $refMailer->getProperty($propName); break; }
        }
        if ($transportProp) {
            $transportProp->setAccessible(true);
            $transport = $transportProp->getValue($this->mailer);
            if (is_iterable($transport)) {
                $i=0; foreach ($transport as $t) { $this->describeTransport($io, $t, '#'.$i++); }
            } else {
                $this->describeTransport($io, $transport, 'principal');
            }
        } else {
            $io->warning('No se pudo acceder a la propiedad del transporte (posible cambio interno de Symfony).');
        }

        $io->success('Fin del diagnóstico');
        return Command::SUCCESS;
    }

    private function describeTransport(SymfonyStyle $io, $transport, string $label): void
    {
        if (!$transport) { $io->warning("Transporte $label es null"); return; }
        $cls = get_class($transport);
        $io->section("Transporte $label: $cls");
        // Intentar obtener un DSN representativo si el transporte lo soporta
        if (method_exists($transport, '__toString')) {
            try { $io->text('DSN (toString): ' . (string)$transport); } catch (\Throwable $e) { /* ignore */ }
        }
        foreach (['getHost','getPort','getEncryption'] as $m) {
            if (method_exists($transport, $m)) {
                try { $io->text($m . ': ' . var_export($transport->$m(), true)); } catch (\Throwable $e) {}
            }
        }
    }
}

