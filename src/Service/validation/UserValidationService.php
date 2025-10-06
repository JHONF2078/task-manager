<?php declare(strict_types=1);

namespace App\Service\validation;

use App\Dto\UserRegistrationInput;
use App\Exception\ValidationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class UserValidationService
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    public function validateAndNormalizeUserData(array $data) : array
    {
        $dto           = new UserRegistrationInput();
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
            'dto'            => $dto,
            'normalizedData' => [
                'email'    => $dto->email,
                'password' => $dto->password,
                'name'     => $dto->name,
            ]
        ];
    }

    public function normalizeRoles(?array $roles = null) : array
    {
        $roles = $roles ?? ['ROLE_USER'];
        if (!is_array($roles)) {
            $roles = ['ROLE_USER'];
        }
        $allowed = ['ROLE_USER', 'ROLE_ADMIN'];
        $roles   = array_values(array_intersect($roles, $allowed));
        if (empty($roles)) {
            $roles = ['ROLE_USER'];
        }
        return $roles;
    }
}
