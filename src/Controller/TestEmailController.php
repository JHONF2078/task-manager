<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class TestEmailController extends AbstractController
{
    #[Route('/test-email', name: 'test_email')]
    public function sendTestEmail(MailerInterface $mailer) : Response
    {
        $email = (new Email())
            ->from('jhonf2077pruebas@gmail.com')
            ->to('jhonf2077pruebas@gmail.com')
            ->subject('Correo de prueba Symfony')
            ->text('Este es un correo de prueba enviado desde Symfony usando Gmail SMTP.');

        try {
            $mailer->send($email);
            return new Response('Correo enviado correctamente.');
        } catch (\Exception $e) {
            return new Response('Error al enviar el correo: ' . $e->getMessage());
        }
    }
}
