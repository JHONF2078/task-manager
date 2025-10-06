<?php declare(strict_types=1);

namespace App\Service\validation;

use App\Dto\UserRegistrationRequest;
use App\Entity\User;
use App\Exception\ValidationException;
use App\Service\Contract\validation\UserValidationServiceInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class UserValidationService implements UserValidationServiceInterface
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    public function validateAndNormalizeUserData(array $data) : array
    {
        $dto           = new UserRegistrationRequest();
        $dto->email    = (string)($data['email'] ?? '');
        $dto->password = (string)($data['password'] ?? '');
        $dto->name     = isset($data['name']) ? (string)$data['name'] : null;

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errs = [];
            foreach ($violations as $v) {
                $errs[] = ['field' => $v->getPropertyPath(), 'message' => $v->getMessage()];
            }
            throw new ValidationException($errs);
        }

        return [
            'email'    => $dto->email,
            'password' => $dto->password,
            'name'     => $dto->name,
        ];
    }

    public function normalizeRoles(?array $roles = null) : array
    {
        $roles = $roles ?? [User::ROLE_USER];
        if (!is_array($roles)) {
            $roles = [User::ROLE_USER];
        }
        $roles = array_values(array_intersect($roles, User::ALLOWED_ROLES));
        if (empty($roles)) {
            $roles = User::ROLE_USER;
        }
        return $roles;
    }
}
