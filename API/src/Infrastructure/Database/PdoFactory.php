<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

final class PdoFactory
{
    /** @param array{dsn:string,user:?string,pass:?string} $dbConfig */
    public function __construct(private array $dbConfig)
    {
    }

    public function create(): PDO
    {
        $driver = strtolower((string) strtok($this->dbConfig['dsn'], ':'));
        $availableDrivers = PDO::getAvailableDrivers();

        if ($driver !== '' && !in_array($driver, $availableDrivers, true)) {
            throw new \RuntimeException(
                sprintf(
                    'Driver PDO non disponibile: %s. Driver disponibili: %s',
                    $driver,
                    implode(', ', $availableDrivers)
                )
            );
        }

        $pdo = new PDO(
            $this->dbConfig['dsn'],
            $this->dbConfig['user'],
            $this->dbConfig['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        return $pdo;
    }
}
