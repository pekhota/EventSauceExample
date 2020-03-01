<?php

namespace App\events;


use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

abstract class LampEvent implements SerializablePayload
{
    public string $location;
    public string $state;
    public AggregateRootId $uid;

    /**
     * LampWasInstalled constructor.
     * @param AggregateRootId $uid
     * @param string $state
     * @param string $location
     */
    public function __construct(AggregateRootId $uid, string $state, string $location)
    {
        $this->uid = $uid;
        $this->state = $state;
        $this->location = $location;
    }

    public function toPayload(): array
    {
        return [
            'uid' => $this->uid->toString(),
            'state' => $this->state,
            'location' => $this->location
        ];
    }
}