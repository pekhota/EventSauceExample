<?php


namespace Pekhota\MySqlMessageRepository;


use App\LampId;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Serialization\MessageSerializer;
use EventSauce\EventSourcing\Snapshotting\Snapshot;
use EventSauce\EventSourcing\Snapshotting\SnapshotRepository;
use PDO;

class MySqlSnapshotRepository implements SnapshotRepository
{
    protected PDO $connection;

    protected string $tableName;

    /**
     * MySqlSnapshotRepository constructor.
     * @param PDO $connection
     * @param string $tableName
     */
    public function __construct(PDO $connection, string $tableName)
    {
        $this->connection = $connection;
        $this->tableName = $tableName;
    }

    public function persist(Snapshot $snapshot): void
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO {$this->tableName} (aggregate_root_id, aggregate_root_version, state)
            VALUES (?, ?, ?)");
        $stmt->execute([
            $snapshot->aggregateRootId()->toString(),
            $snapshot->aggregateRootVersion(),
            json_encode($snapshot->state())
        ]);
    }

    public function retrieve(AggregateRootId $id): ?Snapshot
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM snapshots 
            WHERE aggregate_root_id = ? 
            ORDER BY aggregate_root_version DESC
            LIMIT 1"
        );
        $stmt->execute([$id->toString()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        assert(!empty($row), "Can't retrieve snapshot from database. Try to change it's id ");

        return new Snapshot(
            LampId::fromString($row['aggregate_root_id']),
            $row['aggregate_root_version'],
            json_decode($row['state']));
    }
}