<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

final class AuthSchemaManager
{
    public function __construct(private PDO $pdo)
    {
    }

    public function ensure(): void
    {
        $this->pdo->exec(
            "
            CREATE TABLE IF NOT EXISTS AccountSession (
                sid BIGINT AUTO_INCREMENT PRIMARY KEY,
                aid INT NOT NULL,
                token_hash CHAR(64) NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                revoked_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                CONSTRAINT fk_account_session_account
                    FOREIGN KEY (aid) REFERENCES Account(aid)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                INDEX idx_account_session_aid (aid),
                INDEX idx_account_session_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            "
        );
    }
}
