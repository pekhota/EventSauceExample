<?php declare(strict_types=1);

namespace Pekhota\MySqlMessageRepository;

use DateTime;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\MessageSerializer;
use Exception;
use Generator;
use PDO;
use PDOStatement;

class MySqlMessageRepository implements MessageRepository
{

    protected PDO $connection;
    /**
     * @var MessageSerializer
     */
    protected MessageSerializer $serializer;

    protected string $tableName;

    /**
     * MySqlMessageRepository constructor.
     * @param PDO $connection
     * @param MessageSerializer $messageSerializer
     * @param string $tableName
     */
    public function __construct(PDO $connection, MessageSerializer $messageSerializer, string $tableName)
    {
        $this->connection = $connection;
        $this->serializer = $messageSerializer;
        $this->tableName = $tableName;
    }

    /**
     * @param Message ...$messages
     * @throws Exception
     */
    public function persist(Message ...$messages)
    {
        if(count($messages) === 0) {
            return;
        }

        $pdo = $this->connection;

        /* @var Message $event */

        foreach ($messages as $message) {
            $messageArray = $this->serializer->serializeMessage($message);
            $messageJson = json_encode($messageArray);

            $aggregateRootId = $message->aggregateRootId()->toString();
            $aggregateRootVersion = $message->aggregateVersion();

            $dt = new DateTime($message->header(Header::TIME_OF_RECORDING));
            $timeOfRecording = $dt->format('Y-m-d h:m:s.u');

            $eventType = $message->header(Header::EVENT_TYPE);

            $data = [
                'aggregate_root_id' => $aggregateRootId,
                'aggregate_root_version' => $aggregateRootVersion,
                'event_type' => $eventType,
                'time_of_recording' => $timeOfRecording,
                'message' => $messageJson
            ];

            $statement = "
                INSERT INTO 
                  {$this->tableName} 
                  (".join(',' , array_keys($data)).")
                VALUES (".join(',', array_fill(0, count($data), '?')).") ";

            $stmt = $pdo->prepare($statement);
            $stmt->execute(array_values($data));
        }
    }

    public function retrieveAll(AggregateRootId $id): Generator
    {
        $stmt = $this->connection->prepare("SELECT * FROM {$this->tableName} 
          WHERE aggregate_root_id =?
          ORDER BY aggregate_root_version ASC");
        $stmt->execute([$id->toString()]);

        return $this->yieldMessages($stmt);
    }

    /**
     * @param AggregateRootId $id
     * @param int $aggregateRootVersion
     * @return Generator
     */
    public function retrieveAllAfterVersion(
        AggregateRootId $id,
        int $aggregateRootVersion
    ): Generator {
        $stmt = $this->connection->prepare("SELECT * FROM {$this->tableName} 
          WHERE aggregate_root_id = ? AND aggregate_root_version > ?
          ORDER BY aggregate_root_version ASC");
        $stmt->execute([$id->toString(), $aggregateRootVersion]);

        return $this->yieldMessages($stmt);
    }

    private function yieldMessages(PDOStatement $statement) {
        while ($row = $statement->fetch()) {
            $messages = $this->serializer->unserializePayload(json_decode($row['message'], true));
            foreach ($messages as $message) {
                yield $message;
            }
        }

        return isset($message)
            ? $message->header(Header::AGGREGATE_ROOT_VERSION) ?: 0
            : 0;
    }
}