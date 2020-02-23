<?php

require_once __DIR__ . '/vendor/autoload.php';


use App\Lamp;
use App\LampId;
use EventSauce\EventSourcing\ConstructingAggregateRootRepository;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use Pekhota\MySqlMessageRepository\Connection;
use Pekhota\MySqlMessageRepository\MySqlMessageRepository;


$connection = Connection::getInstance()->getConnection();
$mysqlMessageRepo = new MySqlMessageRepository($connection,
    new ConstructingMessageSerializer(), 'messages');

$aggregateRootRepository = new ConstructingAggregateRootRepository(
    Lamp::class,
    $mysqlMessageRepo,
    null
);

$uniqueId = LampId::fromString(uniqid('', true));

$obj = Lamp::install($uniqueId, Lamp::STATE_OFF, Lamp::LOCATION_KITCHEN);

$obj->turnOn();
$obj->turnOff();
$obj->turnOn();
$obj->turnOff();
$obj->turnOn();
$obj->turnOff();

$aggregateRootRepository->persist($obj);


$generator = $mysqlMessageRepo->retrieveAll($uniqueId);

echo "<pre>";
var_dump(iterator_to_array($generator));
echo "</pre>";

