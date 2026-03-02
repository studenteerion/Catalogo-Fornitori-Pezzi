<?php

declare(strict_types=1);

namespace App\Application\Repository;

use DomainException;
use PDO;
use Throwable;

final class AuthRepository
{
    private const ACCESS_TOKEN_TTL_SECONDS = 28800;

    public function __construct(private PDO $pdo)
    {
    }

    /** @param array{email:string,password:string,fnome:string,indirizzo:string} $data */
    public function registerSupplier(array $data): array
    {
        if ($this->findAccountByEmail($data['email']) !== null) {
            throw new DomainException('Email già registrata.');
        }

        $this->pdo->beginTransaction();

        try {
            $supplierStatement = $this->pdo->prepare(
                'INSERT INTO Fornitori (fnome, indirizzo) VALUES (:fnome, :indirizzo)'
            );
            $supplierStatement->execute([
                'fnome' => $data['fnome'],
                'indirizzo' => $data['indirizzo'],
            ]);

            $fid = (int) $this->pdo->lastInsertId();

            $accountStatement = $this->pdo->prepare(
                'INSERT INTO Account (email, password_hash, ruolo, fid)
                 VALUES (:email, :password_hash, :ruolo, :fid)'
            );
            $accountStatement->execute([
                'email' => $data['email'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'ruolo' => 'FORNITORE',
                'fid' => $fid,
            ]);

            $aid = (int) $this->pdo->lastInsertId();

            $this->pdo->commit();

            return $this->buildAuthPayload($aid);
        } catch (Throwable $throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $throwable;
        }
    }

    public function login(string $email, string $password): ?array
    {
        $account = $this->findAccountByEmail($email);

        if ($account === null || !password_verify($password, (string) $account['password_hash'])) {
            return null;
        }

        return $this->buildAuthPayload((int) $account['aid']);
    }

    public function refresh(string $accessToken): ?array
    {
        $account = $this->findAccountByAccessToken($accessToken);

        if ($account === null) {
            return null;
        }

        $this->revokeToken($accessToken);

        return $this->buildAuthPayload((int) $account['aid']);
    }

    public function logout(string $accessToken): void
    {
        $this->revokeToken($accessToken);
    }

    public function findAccountByAccessToken(string $accessToken): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT a.aid, a.email, a.ruolo, a.fid, f.fnome, f.indirizzo
             FROM AccountSession s
             JOIN Account a ON a.aid = s.aid
             LEFT JOIN Fornitori f ON f.fid = a.fid
             WHERE s.token_hash = :token_hash
               AND s.revoked_at IS NULL
               AND s.expires_at > NOW()
             LIMIT 1'
        );
        $statement->execute([
            'token_hash' => hash('sha256', $accessToken),
        ]);

        $row = $statement->fetch();

        return $row === false ? null : $this->normalizeAccount($row);
    }

    public function getAccountById(int $aid): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT aid, email, ruolo, fid
             FROM Account
             WHERE aid = :aid
             LIMIT 1'
        );
        $statement->execute(['aid' => $aid]);

        $row = $statement->fetch();

        return $row === false ? null : $this->normalizeAccount($row);
    }

    /** @param array<string,mixed> $fields */
    public function updateAccount(int $aid, array $fields): array
    {
        if (!array_key_exists('email', $fields)) {
            throw new DomainException('Nessun campo valido da aggiornare.');
        }

        $email = trim((string) $fields['email']);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Email non valida.');
        }

        $existing = $this->findAccountByEmail($email);
        if ($existing !== null && (int) $existing['aid'] !== $aid) {
            throw new DomainException('Email già registrata.');
        }

        $statement = $this->pdo->prepare(
            'UPDATE Account
             SET email = :email
             WHERE aid = :aid'
        );
        $statement->execute([
            'email' => $email,
            'aid' => $aid,
        ]);

        $account = $this->getAccountById($aid);
        if ($account === null) {
            throw new DomainException('Account non trovato.');
        }

        return $account;
    }

    public function changePassword(int $aid, string $oldPassword, string $newPassword): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT password_hash FROM Account WHERE aid = :aid LIMIT 1'
        );
        $statement->execute(['aid' => $aid]);

        $row = $statement->fetch();
        if ($row === false) {
            throw new DomainException('Account non trovato.');
        }

        if (!password_verify($oldPassword, (string) $row['password_hash'])) {
            return false;
        }

        $update = $this->pdo->prepare(
            'UPDATE Account
             SET password_hash = :password_hash
             WHERE aid = :aid'
        );
        $update->execute([
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'aid' => $aid,
        ]);

        return true;
    }

    public function getSupplierByAccountId(int $aid): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT f.fid, f.fnome, f.indirizzo
             FROM Account a
             JOIN Fornitori f ON f.fid = a.fid
             WHERE a.aid = :aid
             LIMIT 1'
        );
        $statement->execute(['aid' => $aid]);

        $row = $statement->fetch();
        if ($row === false) {
            return null;
        }

        return [
            'fid' => (int) $row['fid'],
            'fnome' => (string) $row['fnome'],
            'indirizzo' => (string) $row['indirizzo'],
        ];
    }

    /** @param array<string,mixed> $fields */
    public function updateSupplierByAccountId(int $aid, array $fields): ?array
    {
        $supplier = $this->getSupplierByAccountId($aid);
        if ($supplier === null) {
            return null;
        }

        $setParts = [];
        $params = ['fid' => $supplier['fid']];

        if (array_key_exists('fnome', $fields)) {
            $setParts[] = 'fnome = :fnome';
            $params['fnome'] = trim((string) $fields['fnome']);
        }

        if (array_key_exists('indirizzo', $fields)) {
            $setParts[] = 'indirizzo = :indirizzo';
            $params['indirizzo'] = trim((string) $fields['indirizzo']);
        }

        if ($setParts === []) {
            throw new DomainException('Nessun campo valido da aggiornare.');
        }

        $statement = $this->pdo->prepare(
            sprintf('UPDATE Fornitori SET %s WHERE fid = :fid', implode(', ', $setParts))
        );
        $statement->execute($params);

        return $this->getSupplierByAccountId($aid);
    }

    private function buildAuthPayload(int $aid): array
    {
        $tokenData = $this->createAccessToken($aid);
        $account = $this->getAccountById($aid);

        if ($account === null) {
            throw new DomainException('Account non trovato.');
        }

        return [
            'accessToken' => $tokenData['token'],
            'expiresAt' => $tokenData['expiresAt'],
            'account' => $account,
        ];
    }

    /** @return array{token:string,expiresAt:string} */
    private function createAccessToken(int $aid): array
    {
        $token = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
        $tokenHash = hash('sha256', $token);
        $createdAt = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', time() + self::ACCESS_TOKEN_TTL_SECONDS);

        $statement = $this->pdo->prepare(
            'INSERT INTO AccountSession (aid, token_hash, expires_at, created_at)
             VALUES (:aid, :token_hash, :expires_at, :created_at)'
        );
        $statement->execute([
            'aid' => $aid,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'created_at' => $createdAt,
        ]);

        return [
            'token' => $token,
            'expiresAt' => $expiresAt,
        ];
    }

    private function revokeToken(string $accessToken): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE AccountSession
             SET revoked_at = :revoked_at
             WHERE token_hash = :token_hash
               AND revoked_at IS NULL'
        );
        $statement->execute([
            'revoked_at' => date('Y-m-d H:i:s'),
            'token_hash' => hash('sha256', $accessToken),
        ]);
    }

    private function findAccountByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT aid, email, password_hash, ruolo, fid
             FROM Account
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /** @param array<string,mixed> $row */
    private function normalizeAccount(array $row): array
    {
        return [
            'aid' => (int) $row['aid'],
            'email' => (string) $row['email'],
            'ruolo' => (string) $row['ruolo'],
            'fid' => $row['fid'] === null ? null : (int) $row['fid'],
        ];
    }
}
