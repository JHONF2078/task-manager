<?php declare(strict_types=1);

namespace App\Controller\Traits;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait PaginateTrait
{
    private const DEFAULT_PAGE  = 1;
    private const DEFAULT_LIMIT = 10;

    /**
     * Extrae y valida los parámetros de paginación de la petición.
     *
     * @param Request $request
     *
     * @return array{int, int} - Un array con [limit, page]
     */
    protected function getPagination(Request $request) : array
    {
        $page  = $request->query->getInt('page', self::DEFAULT_PAGE);
        $limit = $request->query->getInt('limit', self::DEFAULT_LIMIT);

        if ($page < 1) {
            throw new BadRequestHttpException('El parámetro "page" debe ser un entero positivo.');
        }

        if ($limit < 1 || $limit > 100) {
            throw new BadRequestHttpException('El parámetro "limit" debe estar entre 1 y 100.');
        }

        return [$limit, $page];
    }
}
