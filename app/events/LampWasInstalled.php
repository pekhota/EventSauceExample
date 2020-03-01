<?php

declare(strict_types=1);

namespace App\Events;

use App\LampId;

use EventSauce\EventSourcing\Serialization\SerializablePayload;

class LampWasInstalled extends LampEvent
{
    public static function fromPayload(array $payload): SerializablePayload
    {
        return new LampWasInstalled(LampId::fromString($payload['uid']), $payload['state'], $payload['location']);
    }
}