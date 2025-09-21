<?php declare(strict_types=1);

namespace App\Command;

use App\Service\PasswordResetService;
use App\Service\ResetPasswordMailService;
use App\Service\UserService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;

#[AsCommand(name: 'app:mail:test', description: 'Envía un correo de prueba de recuperación (usa el MAILER_DSN configurado).')]
class SendTestMailCommand extends Command
{
    public function __construct(
        private ResetPasswordMailService $resetMailService,
        private MailerInterface $mailer,
        private ParameterBagInterface $params,
        private UserService $userService,
        private PasswordResetService $passwordResetService,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Email de destino (de prueba)')
            ->addArgument('nombre', InputArgument::OPTIONAL, 'Nombre a mostrar', 'Usuario Demo')
            // Opción antigua service-only ya no necesaria, pero la mantenemos inofensiva
            ->addOption('service-only', null, InputOption::VALUE_NONE, 'Enviar solo correo de recuperación (token persistido real).');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io          = new SymfonyStyle($input, $output);
        $dest        = (string)$input->getArgument('email');
        $nombre      = (string)$input->getArgument('nombre');
        $serviceOnly = (bool)$input->getOption('service-only'); // ignorado realmente, flujo único

        if (!filter_var($dest, FILTER_VALIDATE_EMAIL)) {
            $io->error('Email destino inválido');
            return Command::INVALID;
        }

        $io->title('Prueba de envío de correo (reset password)');

        // 1. Mostrar variables relevantes
        $rawDsnEnv = $_ENV['MAILER_DSN']         ?? $_SERVER['MAILER_DSN'] ?? getenv('MAILER_DSN') ?: null;
        $gmailUser = $_ENV['GMAIL_USER']         ?? $_SERVER['GMAIL_USER'] ?? getenv('GMAIL_USER') ?: null;
        $gmailPass = $_ENV['GMAIL_APP_PASSWORD'] ?? $_SERVER['GMAIL_APP_PASSWORD'] ?? getenv('GMAIL_APP_PASSWORD') ?: null;
        $io->section('Variables de entorno detectadas');
        $io->listing([
            'MAILER_DSN = ' . ($rawDsnEnv ? $rawDsnEnv : '(no definido)'),
            'GMAIL_USER = ' . ($gmailUser ?: '(no definido)'),
            'GMAIL_APP_PASSWORD (longitud) = ' . ($gmailPass ? strlen($gmailPass) : 0),
        ]);

        // 2. Intentar leer DSN efectivo desde el contenedor (ParameterBag)
        $resolvedDsn = $this->params->has('mailer.default_transport') ? $this->params->get('mailer.default_transport') : ($rawDsnEnv ?? '(desconocido)');
        $io->writeln('DSN efectivo (best effort): <info>' . $resolvedDsn . '</info>');

        // 3. Test de conexión a smtp.gmail.com:587 (solo si parece Gmail)
        if (str_contains((string)$resolvedDsn, 'gmail') || str_contains((string)$resolvedDsn, 'smtp.gmail.com')) {
            $io->section('Probe smtp.gmail.com:587');
            $err   = '';
            $errno = 0;
            $start = microtime(true);
            $fp    = @stream_socket_client('tcp://smtp.gmail.com:587', $errno, $err, 8, STREAM_CLIENT_CONNECT);
            if ($fp) {
                stream_set_timeout($fp, 5);
                $banner = fgets($fp, 512);
                fclose($fp);
                $io->success('Conectado. Banner: ' . trim((string)$banner));
            } else {
                $io->error("No se pudo conectar a smtp.gmail.com:587 ($errno) $err");
            }
        }

        // Obtener / validar usuario real
        $user = $this->userService->getUserByEmail($dest);
        if (!$user) {
            $io->error('El usuario con email ' . $dest . ' no existe. Regístralo antes de probar el reset.');
            return Command::FAILURE;
        }

        // Generar y persistir token real usando PasswordResetService
        $ttl      = (int)($_ENV['RESET_TOKEN_TTL_MINUTES'] ?? 60);
        $token    = $this->passwordResetService->generateResetToken($user, $ttl);
        $base     = rtrim($_ENV['FRONTEND_BASE_URL'] ?? 'http://localhost:8000', '/');
        $resetUrl = $base . '/reset-password/' . $token;

        $io->section('Token generado');
        $io->listing([
            'Token (longitud ' . strlen($token) . ') = ' . $token,
            'Expira en minutos: ' . $ttl,
            'Reset URL = ' . $resetUrl,
        ]);

        // Enviar correo usando el servicio central (plantillas)
        $this->resetMailService->send($user, $token, $resetUrl);
        $io->success('Correo de recuperación enviado (token persistido). Usa exactamente la URL mostrada para probar el flujo.');
        $io->note('Si abres un email anterior, puede que el token ya haya sido reemplazado. Usa siempre el último generado.');

        return Command::SUCCESS;
    }
}
