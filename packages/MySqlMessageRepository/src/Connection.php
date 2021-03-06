<?php declare(strict_types=1);

namespace Pekhota\MySqlMessageRepository;

use PDO;

class Connection
{
    private ?PDO $connection = null;
    private static ?self $instance = null;

    /**
     * Connection constructor.
     * @param array|null $settings
     */
    private function __construct(array $settings = null)
    {
        $host = $settings['host'] ?? 'mysql';
        $db = $settings['db'] ?? 'app';
        $user = $settings['user'] ?? 'root';
        $pass = $settings['password'] ?? 'example';
        $charset = $settings['charset'] ?? 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
            $this->connection = $pdo;
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * @return PDO
     */
    public function getConnection() : PDO {
        return $this->connection;
    }

    public static function getInstance(array $settings = null) : self
    {
        if(empty(self::$instance)) {
            self::$instance = new self($settings);
        }

        return self::$instance;
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }
}