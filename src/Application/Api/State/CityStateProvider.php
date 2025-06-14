<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\City\Query\GetCityViewQuery;
use App\Domain\City\ValueObject\CityId;
use App\Infrastructure\City\ReadModel\Doctrine\CityViewRepository;
use App\UI\Api\Resource\CityResource;
use App\UI\City\ViewModel\CityView;
use Ecotone\Modelling\QueryBus;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class CityStateProvider implements ProviderInterface
{
    public function __construct(
        private QueryBus              $queryBus,
        private CityViewRepository    $cityViewRepository,
        private ObjectMapperInterface $objectMapper,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (isset($uriVariables['cityId'])) {
            return $this->provideCity($uriVariables['cityId']);
        }

        return $this->provideCitiesForGame($uriVariables['gameId']);
    }

    private function provideCity(string $cityId): CityResource
    {
        try {
            /** @var CityView $cityView */
            $cityView = $this->queryBus->send(new GetCityViewQuery(new CityId($cityId)));
        } catch (\RuntimeException $e) {
            throw new NotFoundHttpException("City with ID $cityId not found");
        }
        return $this->objectMapper->map($cityView, CityResource::class);
    }

    private function provideCitiesForGame(string $gameId): array
    {
        $cityViewEntities = $this->cityViewRepository->findByGameId($gameId);

        return array_map(
            fn($cityView) => $this->objectMapper->map($cityView, CityResource::class),
            $cityViewEntities
        );
    }
}
