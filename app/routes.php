<?php
use App\Lamp;
use App\LampId;
use App\Consumers\LampMySqlConsumer;
use EventSauce\EventSourcing\ConstructingAggregateRootRepository;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\EventSourcing\Snapshotting\ConstructingAggregateRootRepositoryWithSnapshotting;
use EventSauce\EventSourcing\SynchronousMessageDispatcher;
use Pekhota\MySqlMessageRepository\Connection;
use Pekhota\MySqlMessageRepository\MySqlMessageRepository;
use Pekhota\MySqlMessageRepository\MySqlSnapshotRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;

$routes = new RouteCollection();

$routes->add('hello', new Route('/hello/{name}', [
        '_controller' => function (Request $request) {
            return new Response(
                sprintf("Hello %s", $request->get('name'))
            );
        }]
));

function getRootRepository() {
    $messageDispatcher = new SynchronousMessageDispatcher(new LampMySqlConsumer());

    $connection = Connection::getInstance()->getConnection();
    $mysqlMessageRepo = new MySqlMessageRepository($connection,
        new ConstructingMessageSerializer(), 'messages');

    $mysqlSnapshotRepo = new MySqlSnapshotRepository($connection, 'snapshots');

    $aggregateRootRepository = new ConstructingAggregateRootRepository(
        Lamp::class,
        $mysqlMessageRepo,
        $messageDispatcher
    );

    return new ConstructingAggregateRootRepositoryWithSnapshotting(
        Lamp::class,
        $mysqlMessageRepo,
        $mysqlSnapshotRepo,
       $aggregateRootRepository
    );
}

$routes->add('lamps_install', new Route('/lamps/install', [
        '_controller' => function (Request $request) {

            $aggregateRootRepository = getRootRepository();

            $uuid4 = Uuid::uuid4();

            $uniqueId = LampId::fromString($uuid4->toString());

            $aggregateRoot = Lamp::install($uniqueId, Lamp::STATE_OFF, Lamp::LOCATION_KITCHEN);
            $aggregateRootRepository->persist($aggregateRoot);
            $aggregateRootRepository->storeSnapshot($aggregateRoot);

            return new JsonResponse([
                'data' => $aggregateRootRepository->retrieve($uniqueId),
                'meta' => [
                    'switch_on' => "/lamps/{$uuid4->toString()}/switch"
                ]
            ]);
        }]
));

$routes->add('lamps_id_switch', new Route('/lamps/{root_id}/switch', [
        '_controller' => function (Request $request) {

            $aggregateRootRepository = getRootRepository();
            $aggregateRootId = LampId::fromString($request->get('root_id'));

            $aggregateRoot = $aggregateRootRepository->retrieveFromSnapshot($aggregateRootId);

            if($aggregateRoot->state === Lamp::STATE_OFF) {
                $aggregateRoot->turnOn();
            } else {
                $aggregateRoot->turnOff();
            }

            $aggregateRootRepository->persist($aggregateRoot);
            $aggregateRootRepository->storeSnapshot($aggregateRoot);

            return new JsonResponse([
                'data' => $aggregateRootRepository->retrieve($aggregateRootId),
            ]);
        }]
));

return $routes;
