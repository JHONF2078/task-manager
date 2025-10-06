<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Entity\User;

/**
 * Interface para AuthRepository
 * Solo declara métodos personalizados de negocio de autenticación
 */
interface AuthRepositoryInterface
{
    /**
     * Métodos estándar de Doctrine
     */
    public function find($id, $lockMode = null, $lockVersion = null);
    public function findAll();
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null);
    public function findOneBy(array $criteria, array $orderBy = null);

    /**
     * Busca un usuario por email.
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User;

    /**
     * Verifica si un email ya está registrado.
     * @param string $email
     * @return bool
     */
    public function isEmailTaken(string $email): bool;

    /**
     * Guarda un usuario.
     * @param User $user
     * @return void
     */
    public function save(User $user): void;
}
