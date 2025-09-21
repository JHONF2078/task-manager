<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Yaml\Yaml;

#[Route('/api')] // base for API docs endpoints
class OpenApiController extends AbstractController
{
    #[Route('/docs.json', name: 'api_docs_json', methods: ['GET'])]
    public function jsonSpec() : JsonResponse
    {
        $file = $this->getParameter('kernel.project_dir') . '/config/openapi.yaml';
        if (!is_file($file)) {
            return new JsonResponse(['error' => 'OpenAPI spec no encontrada'], 404);
        }
        try {
            $spec = Yaml::parseFile($file);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Error parseando openapi.yaml', 'detail' => $e->getMessage()], 500);
        }
        return new JsonResponse($spec, 200, [ 'Cache-Control' => 'no-store' ]);
    }

    #[Route('/docs', name: 'api_docs_ui', methods: ['GET'])]
    public function swaggerUi() : Response
    {
        // Simple Swagger UI via CDN pointing to /api/docs.json
        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>API Docs</title>
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
  <style>body { margin:0; background:#1e1e1e;} .topbar{display:none;} </style>
</head>
<body>
  <div id="swagger-ui"></div>
  <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
  <script>
    window.onload = () => {
      SwaggerUIBundle({
        url: '/api/docs.json',
        dom_id: '#swagger-ui',
        presets: [SwaggerUIBundle.presets.apis],
        layout: 'BaseLayout'
      });
    };
  </script>
</body>
</html>
HTML;
        return new Response($html);
    }
}
