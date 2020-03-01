<?php


namespace App\Consumers;


use App\events\LampEvent;
use App\Events\LampWasInstalled;
use App\Events\LampWasTurnedOff;
use App\Events\LampWasTurnedOn;
use EventSauce\EventSourcing\Consumer;
use EventSauce\EventSourcing\Message;
use Pekhota\MySqlMessageRepository\Connection;

class LampMySqlConsumer implements Consumer
{

    public function handle(Message $message)
    {
        $event = $message->event();

        // todo add do container
        $connection = Connection::getInstance()->getConnection();
        if($event instanceof LampEvent) {

            $location = $event->location;
            $state = $event->state;
            $uid = $event->uid->toString();

            if($event instanceof LampWasInstalled) {
                $connection->prepare(
                    "INSERT INTO `lamps` (uid, location, state) VALUES (?,?,?)"
                )->execute([$uid, $location, $state]);
            } else if($event instanceof LampWasTurnedOff || $event instanceof LampWasTurnedOn) {
                $stmt = $connection->prepare(
                    "UPDATE `lamps` SET location = ?, state = ? WHERE uid = ?"
                );
                $stmt->execute([$location, $state, $uid]);
            }
        }

    }
}