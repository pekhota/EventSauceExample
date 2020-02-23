<?php

declare(strict_types=1);

namespace App\Events;

use App\LampId;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

class LampWasInstalled implements SerializablePayload
{
    public $location;
    public $state;
    public $uid;

    /**
     * LampWasInstalled constructor.
     * @param $location
     * @param $state
     * @param $uid
     */
    public function __construct(LampId $uid, $state, $location)
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

    public static function fromPayload(array $payload): SerializablePayload
    {
        return new LampWasInstalled(LampId::fromString($payload['uid']), $payload['state'], $payload['location']);
    }
}