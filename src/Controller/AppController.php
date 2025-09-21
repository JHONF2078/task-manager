<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AppController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    #[Route('/{path}', name: 'app_spa', requirements: ['path' => '^(?!api).+'], methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('home/app.html.twig');
    }
}
