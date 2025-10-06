<?php declare(strict_types=1);

namespace App\Helper;

use App\Mapper\Manual\TaskResponseMapper;
use AutoMapperPlus\AutoMapperInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

class MapperHelper
{
    // Estrategias de mapeo con sus propósitos específicos
    public const STRATEGY_SERIALIZER          = 'serializer';         // Entidad -> Array/JSON (directo)
    public const STRATEGY_AUTO_MAPPER         = 'auto_mapper';        // Entidad -> DTO -> Array/JSON (automático)
    public const STRATEGY_MANUAL_MAPPER       = 'manual_mapper';      // Entidad -> DTO
    public const STRATEGY_MANUAL_MAPPER_FULL  = 'manual_mapper_full'; // Entidad -> DTO -> Array/JSON (manual completo)

    private array $defaultContext = [
        'groups'          => ['task:read'],
        'datetime_format' => \DateTimeInterface::ATOM,
        'skip_null_values' => false // Asegurarnos que los valores null se incluyan
    ];

    public function __construct(
        private readonly ParameterBagInterface $params,
        private readonly AutoMapperInterface $autoMapper,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * Mapea una entidad individual usando la estrategia especificada
     *
     * @param object $source Entidad a mapear
     * @param string $dtoClass Clase del DTO destino
     * @param string|null $strategy Estrategia de mapeo a usar
     * @return mixed El resultado del mapeo (DTO o array según la estrategia)
     */
    public function map(object $source, string $dtoClass, ?string $strategy = null)
    {
        $strategy = $this->getMapperStrategy($strategy);

        return match($strategy) {
            self::STRATEGY_AUTO_MAPPER => $this->autoMapper->map($source, $dtoClass),
            self::STRATEGY_MANUAL_MAPPER => TaskResponseMapper::toDto($source),
            self::STRATEGY_MANUAL_MAPPER_FULL => $this->mapWithManualMapperFull([$source])[0],
            default => $this->serializer->normalize($source, null, $this->defaultContext)
        };
    }

    /**
     * Mapea una colección de entidades usando la estrategia especificada
     *
     * @param array $entities Colección de entidades a mapear
     * @param string $dtoClass Clase del DTO destino
     * @param string|null $strategy Estrategia de mapeo a usar
     * @return array Colección de resultados mapeados
     */
    public function mapCollection(array $entities, string $dtoClass, ?string $strategy = null): array
    {
        if (empty($entities)) {
            return [];
        }

        $strategy = $this->getMapperStrategy($strategy);

        return match($strategy) {
            self::STRATEGY_AUTO_MAPPER => array_map(
                fn ($entity) => $this->autoMapper->map($entity, $dtoClass),
                $entities
            ),
            self::STRATEGY_MANUAL_MAPPER => array_map(
                fn ($entity) => TaskResponseMapper::toDto($entity),
                $entities
            ),
            self::STRATEGY_MANUAL_MAPPER_FULL => $this->mapWithManualMapperFull($entities),
            default => $this->serializer->normalize($entities, null, $this->defaultContext)
        };
    }

    /**
     * Estrategia: Manual Mapper (Proceso completo)
     * Usar cuando:
     * - Se necesita convertir de entidad a array JSON pasando por DTO
     * - Se quiere usar el serializador directamente sobre el DTO
     * - Se necesita control total sobre la transformación y serialización
     *
     * @param array $entities Colección de entidades a mapear
     * @return array Colección de arrays normalizados
     */
    private function mapWithManualMapperFull(array $entities): array
    {
        $serializationContext = [
            'groups' => ['task:read'],
            'datetime_format' => \DateTimeInterface::ATOM,
            'skip_null_values' => false,
            'enable_max_depth' => true,
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ];

        $result = [];
        foreach ($entities as $entity) {
            $dto = TaskResponseMapper::toDto($entity);
            $result[] = $this->serializer->normalize($dto, null, $serializationContext);
        }
        return $result;
    }

    /**
     * Deserializa datos JSON a un objeto
     *
     * @param string $data JSON string a deserializar
     * @param string $type Clase objetivo (DTO o entidad)
     * @param string $format Formato de los datos ('json' por defecto)
     * @return mixed Objeto deserializado
     */
    public function deserialize(string $data, string $type, string $format = 'json')
    {
        return $this->serializer->deserialize($data, $type, $format);
    }

    /**
     * Obtiene la estrategia de mapeo a usar
     *
     * @param string|null $strategy Estrategia específica o null para usar la configurada
     * @return string Estrategia de mapeo a usar
     */
    private function getMapperStrategy(?string $strategy = null): string
    {
        return $strategy ?? $this->params->get('mapper_strategy', self::STRATEGY_SERIALIZER);
    }
}
