<?php

declare(strict_types=1);

namespace App;

use EventSauce\EventSourcing\AggregateRootId;
use JsonSerializable;

class LampId implements AggregateRootId, JsonSerializable
{
    private $id;

    private function __construct(string $id)
    {
        $this->id = $id;
    }

    public function toString(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return AggregateRootId
     */
    public static function fromString(string $id): AggregateRootId
    {
        return new self($id);
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}