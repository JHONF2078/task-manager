<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PasswordHasherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:create-admin', description: 'Crea un usuario administrador inicial si no existe')]
class CreateAdminCommand extends Command
{
    public function __construct(private EntityManagerInterface $em, private UserRepository $userRepository, private PasswordHasherService $passwordHasher)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $existing = $this->userRepository->findOneBy(['email' => 'admin@miapp.com']);
        if ($existing) {
            $output->writeln('<comment>El usuario admin@miapp.com ya existe. Nada que hacer.</comment>');
            return Command::SUCCESS;
        }

        $user = new User();
        $user->setEmail('admin@miapp.com');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hash('admin123'));
        $this->em->persist($user);
        $this->em->flush();

        $output->writeln('Usuario admin creado correctamente.');
        return Command::SUCCESS;
    }
}
