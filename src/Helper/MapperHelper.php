<?php declare(strict_types=1);

namespace App\Helper;

use AutoMapperPlus\AutoMapperInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

class MapperHelper
{



    public const STRATEGY_SERIALIZER    = 'serializer';

    // un objeto en otro otro  de entidad a DTO y de DTO a entidad
    // el controller convierte a JSON mediante $this->json()
    public const STRATEGY_AUTO_MAPPER   = 'auto_mapper';


    // un objeto en otro otro  de entidad a DTO y de DTO a entidad
    // un objeto en array
    // el controller convierte a JSON mediante $this->json()
    public const STRATEGY_MANUAL_MAPPER = 'manual_mapper'; // Entidad -> DTO o Array (manual)

    private array $defaultContext = [
        'groups'           => ['task:read'],
        'datetime_format'  => \DateTimeInterface::ATOM,
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
     * @param object      $source            Entidad a mapear
     * @param string      $dtoClass          Clase del DTO destino
     * @param string|null $strategy          Estrategia de mapeo a usar
     * @param string|null $manualMapperClass Clase del mapeador manual a usar
     * @param string      $manualMethod      Metodo del mapeador manual ('toDto' o 'toArray')
     *
     * @return mixed El resultado del mapeo (DTO o array según la estrategia)
     */
    public function map(
        object $source,
        string $dtoClass,
        ?string $strategy = null,
        ?string $manualMapperClass = null,
        string $manualMethod = 'toDto'
    ) {
        $strategy = $this->getMapperStrategy($strategy);

        return match($strategy) {
            self::STRATEGY_AUTO_MAPPER   => $this->autoMapper->map($source, $dtoClass),
            self::STRATEGY_MANUAL_MAPPER => $manualMapperClass::$manualMethod($source),
            default                      => $this->serializer->normalize($source, null, $this->defaultContext)
        };
    }

    /**
     * Mapea una colección de entidades usando la estrategia especificada
     *
     * @param array       $entities          Colección de entidades a mapear
     * @param string      $dtoClass          Clase del DTO destino
     * @param string|null $strategy          Estrategia de mapeo a usar
     * @param string|null $manualMapperClass Clase del mapeador manual a usar
     * @param string      $manualMethod      Método del mapeador manual ('toDto' o 'toArray')
     *
     * @return array Colección de resultados mapeados
     */
    public function mapCollection(
        array $entities,
        string $dtoClass,
        ?string $strategy = null,
        ?string $manualMapperClass = null,
        string $manualMethod = 'toDto'
    ) : array {
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
                fn ($entity) => $manualMapperClass::$manualMethod($entity),
                $entities
            ),
            default => $this->serializer->normalize($entities, null, $this->defaultContext)
        };
    }

    /**
     * Deserializa datos JSON a un objeto
     *
     * @param string $data   JSON string a deserializar
     * @param string $type   Clase objetivo (DTO o entidad)
     * @param string $format Formato de los datos ('json' por defecto)
     *
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
     *
     * @return string Estrategia de mapeo a usar
     */
    private function getMapperStrategy(?string $strategy = null) : string
    {
        return $strategy ?? $this->params->get('mapper_strategy', self::STRATEGY_SERIALIZER);
    }
}
