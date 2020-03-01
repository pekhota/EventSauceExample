<?php

declare(strict_types=1);

namespace App;

use App\Events\LampWasInstalled;
use App\Events\LampWasTurnedOff;
use App\Events\LampWasTurnedOn;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Snapshotting\AggregateRootWithSnapshotting;
use EventSauce\EventSourcing\Snapshotting\Snapshot;
use EventSauce\EventSourcing\Snapshotting\SnapshottingBehaviour;
use Generator;

class Lamp implements AggregateRootWithSnapshotting
{
    const STATE_ON = 'ON';
    const STATE_OFF = 'OFF';

    const LOCATION_KITCHEN = 'kitchen';

    public string $state;
    public string $location;

    use AggregateRootBehaviour, SnapshottingBehaviour;

    public static function install(AggregateRootId $id, $state, $location): Lamp
    {
        $process = new self($id);
        $process->recordThat(new LampWasInstalled($id, $state, $location));

        return $process;
    }

    public function turnOff()
    {
        $this->state = self::STATE_OFF;
        $this->recordThat(new LampWasTurnedOff($this->aggregateRootId(), $this->state,
            $this->location));
    }

    public function turnOn()
    {
        $this->state = self::STATE_ON;
        $this->recordThat(new LampWasTurnedOn($this->aggregateRootId(), $this->state,
            $this->location));
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

    protected function createSnapshotState()
    {
        return [
            'location' => $this->location,
            'state' => $this->state
        ];
    }

    protected static function reconstituteFromSnapshotState(
        AggregateRootId $id,
        $state
    ): AggregateRootWithSnapshotting {
        $state = (array)$state;

        $lamp = new static($id);
        $lamp->state = $state['state'];
        $lamp->location = $state['location'];

        return $lamp;
    }
}