<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(name: 'app:send-test-email', description: 'Envía un correo de prueba usando la configuración actual.')]
class SendTestEmailCommand extends Command
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        parent::__construct();
        $this->mailer = $mailer;
    }

    protected function configure()
    {
        $this->setDescription('Envía un correo de prueba usando la configuración actual.');
        $this->addArgument('to', InputArgument::REQUIRED, 'Email de destino');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $to = $input->getArgument('to');
        $envDsn = $_ENV['MAILER_DSN'] ?? $_SERVER['MAILER_DSN'] ?? getenv('MAILER_DSN');
        $output->writeln('<comment>MAILER_DSN efectivo:</comment> ' . $envDsn);

        // Inspección del transporte interno vía reflexión
        try {
            $ref = new \ReflectionObject($this->mailer);
            foreach (['transport','transports'] as $propName) {
                if ($ref->hasProperty($propName)) {
                    $p = $ref->getProperty($propName); $p->setAccessible(true);
                    $val = $p->getValue($this->mailer);
                    $output->writeln('<comment>Propiedad interna \''.$propName.'\':</comment> ' . get_debug_type($val));
                    if (is_iterable($val)) {
                        $i=0; foreach ($val as $inner) { $this->dumpTransport($output, $inner, '#'.$i++); }
                    } else { $this->dumpTransport($output, $val, 'principal'); }
                    break;
                }
            }
        } catch (\Throwable $e) {
            $output->writeln('<error>No se pudo inspeccionar el transporte: '.$e->getMessage().'</error>');
        }

        $email = (new Email())
            ->from('jhonf2077pruebas@gmail.com')
            ->to($to)
            ->subject('Correo de prueba Symfony '.date('H:i:s'))
            ->text("Este es un correo de prueba enviado desde Symfony usando Gmail SMTP.\nTS=".time())
            ->html('<p>Prueba <strong>Symfony Mailer</strong> '.date('c').'</p>');
        $email->getHeaders()->addTextHeader('X-Debug-Test', 'probe-'.time());

        try {
            $this->mailer->send($email);
            $msgId = $email->getHeaders()->has('Message-ID') ? $email->getHeaders()->get('Message-ID')->getBodyAsString() : '(no asignado)';
            $output->writeln('<info>Correo enviado correctamente a ' . $to . '.</info>');
            $output->writeln('<comment>Message-ID:</comment> ' . $msgId);
            $output->writeln('<comment>Headers:</comment>');
            foreach ($email->getHeaders()->all() as $h) {
                $output->writeln('  - '.$h->getName().': '.$h->getBodyAsString());
            }
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error al enviar el correo: ' . $e->getMessage() . '</error>');
            if ($e->getPrevious()) {
                $output->writeln('<error>Prev: '.$e->getPrevious()->getMessage().'</error>');
            }
            return Command::FAILURE;
        }
    }

    private function dumpTransport(OutputInterface $output, $transport, string $label): void
    {
        if (!$transport) { $output->writeln("<error>Transporte $label es null</error>"); return; }
        $cls = get_class($transport);
        $output->writeln('  * Transporte '.$label.': '.$cls);
        foreach (['__toString','getHost','getPort','getEncryption'] as $m) {
            if (method_exists($transport, $m)) {
                try {
                    $val = $transport->$m();
                    if (is_object($val)) $val = get_debug_type($val);
                    $output->writeln('    - '.$m.': '.var_export($val, true));
                } catch (\Throwable $e) {}
            }
        }
    }
}
