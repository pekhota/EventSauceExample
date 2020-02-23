<?php

declare(strict_types=1);

namespace App;

use App\Events\LampWasInstalled;
use App\Events\LampWasTurnedOff;
use App\Events\LampWasTurnedOn;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;

class Lamp implements AggregateRoot
{
    const STATE_ON = 'ON';
    const STATE_OFF = 'OFF';

    const LOCATION_KITCHEN = 'kitchen';

    public $state;
    public $location;

    use AggregateRootBehaviour;

    public static function install(LampId $id, $state, $location): Lamp
    {
        $process = new static($id);
        $process->recordThat(new LampWasInstalled($id, $state, $location));

        return $process;
    }

    public function turnOff() {
        $this->state = self::STATE_OFF;
        $this->recordThat(new LampWasTurnedOff($this->aggregateRootId(), $this->state, $this->location));
    }

    public function turnOn() {
        $this->state = self::STATE_ON;
        $this->recordThat(new LampWasTurnedOn($this->aggregateRootId(), $this->state, $this->location));
    }

    public function applyLampWasInstalled(LampWasInstalled $lampWasInstalled)
    {
        $this->location = $lampWasInstalled->location;
        $this->state = $lampWasInstalled->state;
    }

    public function applyLampWasTurnedOn(LampWasTurnedOn $lampWasInstalled)
    {
        $this->location = $lampWasInstalled->location;
        $this->state = $lampWasInstalled->state;
    }

    public function applyLampWasTurnedOff(LampWasTurnedOff $lampWasInstalled)
    {
        $this->location = $lampWasInstalled->location;
        $this->state = $lampWasInstalled->state;
    }
}