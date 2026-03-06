<?php

declare(strict_types=1);

namespace App\Application\Repository;

use DomainException;
use PDO;

final class AdminRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function listSuppliers(): array
    {
        return $this->pdo->query('SELECT fid, fnome, indirizzo FROM Fornitori ORDER BY fid')->fetchAll();
    }

    public function createSupplier(string $name, string $address): array
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO Fornitori (fnome, indirizzo) VALUES (:fnome, :indirizzo)'
        );
        $statement->execute([
            'fnome' => $name,
            'indirizzo' => $address,
        ]);

        return $this->getSupplierById((int) $this->pdo->lastInsertId());
    }

    /** @param array<string,mixed> $fields */
    public function updateSupplier(int $fid, array $fields): array
    {
        if ($this->getSupplierById($fid) === null) {
            throw new DomainException('Fornitore non trovato.');
        }

        $setParts = [];
        $params = ['fid' => $fid];

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

        $statement = $this->pdo->prepare(sprintf('UPDATE Fornitori SET %s WHERE fid = :fid', implode(', ', $setParts)));
        $statement->execute($params);

        $supplier = $this->getSupplierById($fid);
        if ($supplier === null) {
            throw new DomainException('Fornitore non trovato.');
        }

        return $supplier;
    }

    public function deleteSupplier(int $fid): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM Fornitori WHERE fid = :fid');
        $statement->execute(['fid' => $fid]);

        return $statement->rowCount() > 0;
    }

    /** @return array<int,array<string,mixed>> */
    public function listParts(): array
    {
        return $this->pdo->query('SELECT pid, pnome, colore FROM Pezzi ORDER BY pid')->fetchAll();
    }

    public function createPart(string $name, string $color): array
    {
        $statement = $this->pdo->prepare('INSERT INTO Pezzi (pnome, colore) VALUES (:pnome, :colore)');
        $statement->execute([
            'pnome' => $name,
            'colore' => $color,
        ]);

        return $this->getPartById((int) $this->pdo->lastInsertId());
    }

    /** @param array<string,mixed> $fields */
    public function updatePart(int $pid, array $fields): array
    {
        $setParts = [];
        $params = ['pid' => $pid];

        if (array_key_exists('pnome', $fields)) {
            $setParts[] = 'pnome = :pnome';
            $params['pnome'] = trim((string) $fields['pnome']);
        }

        if (array_key_exists('colore', $fields)) {
            $setParts[] = 'colore = :colore';
            $params['colore'] = trim((string) $fields['colore']);
        }

        if ($setParts === []) {
            throw new DomainException('Nessun campo valido da aggiornare.');
        }

        $statement = $this->pdo->prepare(sprintf('UPDATE Pezzi SET %s WHERE pid = :pid', implode(', ', $setParts)));
        $statement->execute($params);

        return $this->getPartById($pid);
    }

    public function deletePart(int $pid): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM Pezzi WHERE pid = :pid');
        $statement->execute(['pid' => $pid]);

        return $statement->rowCount() > 0;
    }

    /** @return array<int,array<string,mixed>> */
    public function listCatalog(): array
    {
        $statement = $this->pdo->query(
            'SELECT c.fid, c.pid, c.costo, f.fnome, p.pnome
             FROM Catalogo c
             LEFT JOIN Fornitori f ON c.fid = f.fid
             LEFT JOIN Pezzi p ON c.pid = p.pid
             ORDER BY c.fid, c.pid'
        );

        return $statement->fetchAll();
    }

    public function createCatalogItem(int $fid, int $pid, float $cost): array
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO Catalogo (fid, pid, costo) VALUES (:fid, :pid, :costo)'
        );
        $statement->execute([
            'fid' => $fid,
            'pid' => $pid,
            'costo' => $cost,
        ]);

        return $this->getCatalogItem($fid, $pid);
    }

    public function updateCatalogItem(int $fid, int $pid, float $cost): array
    {
        $statement = $this->pdo->prepare(
            'UPDATE Catalogo
             SET costo = :costo
             WHERE fid = :fid AND pid = :pid'
        );
        $statement->execute([
            'costo' => $cost,
            'fid' => $fid,
            'pid' => $pid,
        ]);

        if ($statement->rowCount() === 0) {
            throw new DomainException('Record catalogo non trovato.');
        }

        return $this->getCatalogItem($fid, $pid);
    }

    public function deleteCatalogItem(int $fid, int $pid): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM Catalogo WHERE fid = :fid AND pid = :pid');
        $statement->execute([
            'fid' => $fid,
            'pid' => $pid,
        ]);

        return $statement->rowCount() > 0;
    }

    /** @return array<string,mixed> */
    public function getSupplierById(int $fid): ?array
    {
        $statement = $this->pdo->prepare('SELECT fid, fnome, indirizzo FROM Fornitori WHERE fid = :fid LIMIT 1');
        $statement->execute(['fid' => $fid]);

        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    /** @return array<string,mixed> */
    public function getPartById(int $pid): array
    {
        $statement = $this->pdo->prepare('SELECT pid, pnome, colore FROM Pezzi WHERE pid = :pid LIMIT 1');
        $statement->execute(['pid' => $pid]);

        $row = $statement->fetch();
        if ($row === false) {
            throw new DomainException('Pezzo non trovato.');
        }

        return $row;
    }

    /** @return array<string,mixed> */
    private function getCatalogItem(int $fid, int $pid): array
    {
        $statement = $this->pdo->prepare(
            'SELECT c.fid, c.pid, c.costo, f.fnome, p.pnome
             FROM Catalogo c
             LEFT JOIN Fornitori f ON c.fid = f.fid
             LEFT JOIN Pezzi p ON c.pid = p.pid
             WHERE c.fid = :fid AND c.pid = :pid
             LIMIT 1'
        );
        $statement->execute([
            'fid' => $fid,
            'pid' => $pid,
        ]);

        $row = $statement->fetch();
        if ($row === false) {
            throw new DomainException('Record catalogo non trovato.');
        }

        return $row;
    }

    // ==================== Query Methods ====================

    public function listQuery(): array
    {
        $statement = $this->pdo->query('SELECT qid, descrizione FROM Query ORDER BY qid');
        return $statement->fetchAll();
    }

    public function getQueryById(int $qid): ?array
    {
        $statement = $this->pdo->prepare('SELECT qid, descrizione FROM Query WHERE qid = :qid LIMIT 1');
        $statement->execute(['qid' => $qid]);

        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public function createQuery(string $descrizione): array
    {
        $statement = $this->pdo->prepare('INSERT INTO Query (descrizione) VALUES (:descrizione)');
        $statement->execute(['descrizione' => $descrizione]);

        $qid = (int) $this->pdo->lastInsertId();
        return ['qid' => $qid, 'descrizione' => $descrizione];
    }

    public function updateQuery(int $qid, array $fields): array
    {
        $descrizione = trim((string) ($fields['descrizione'] ?? ''));

        if ($descrizione === '') {
            throw new DomainException('Campo richiesto: descrizione.');
        }

        $statement = $this->pdo->prepare('UPDATE Query SET descrizione = :descrizione WHERE qid = :qid');
        $affected = $statement->execute([
            'descrizione' => $descrizione,
            'qid' => $qid,
        ]);

        if ($statement->rowCount() === 0) {
            throw new DomainException('Query non trovata.');
        }

        return ['qid' => $qid, 'descrizione' => $descrizione];
    }

    public function deleteQuery(int $qid): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM Query WHERE qid = :qid');
        $statement->execute(['qid' => $qid]);

        return $statement->rowCount() > 0;
    }

    // ==================== Account Methods ====================

    public function listAccounts(): array
    {
        $statement = $this->pdo->query(
            'SELECT a.aid, a.email, a.ruolo, a.fid, f.fnome 
             FROM Account a 
             LEFT JOIN Fornitori f ON a.fid = f.fid 
             ORDER BY a.aid'
        );
        return $statement->fetchAll();
    }

    public function getSupplierAccountById(int $aid): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT a.aid, a.email, a.ruolo, a.fid, f.fnome
             FROM Account a
             LEFT JOIN Fornitori f ON a.fid = f.fid
             WHERE a.aid = :aid
             LIMIT 1'
        );
        $statement->execute(['aid' => $aid]);
        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        if (strtolower((string) $row['ruolo']) === 'admin') {
            throw new DomainException('Operazione consentita solo per account fornitore.');
        }

        return $row;
    }

    public function createSupplierAccount(string $email, string $password, int $fid): array
    {
        if ($this->emailExists($email)) {
            throw new DomainException('Email già registrata.');
        }

        if (!$this->supplierExists($fid)) {
            throw new DomainException('Fornitore non trovato.');
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO Account (email, password_hash, ruolo, fid)
             VALUES (:email, :password_hash, :ruolo, :fid)'
        );
        $statement->execute([
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'ruolo' => 'FORNITORE',
            'fid' => $fid,
        ]);

        $aid = (int) $this->pdo->lastInsertId();
        $account = $this->getSupplierAccountById($aid);
        if ($account === null) {
            throw new DomainException('Account non trovato.');
        }

        return $account;
    }

    /** @param array<string,mixed> $fields */
    public function updateSupplierAccount(int $aid, array $fields): array
    {
        $current = $this->getSupplierAccountById($aid);
        if ($current === null) {
            throw new DomainException('Account non trovato.');
        }

        $setParts = [];
        $params = ['aid' => $aid];

        if (array_key_exists('email', $fields)) {
            $email = trim((string) $fields['email']);
            if ($email === '') {
                throw new DomainException('Email non valida.');
            }
            if ($this->emailExists($email, $aid)) {
                throw new DomainException('Email già registrata.');
            }
            $setParts[] = 'email = :email';
            $params['email'] = $email;
        }

        if (array_key_exists('fid', $fields)) {
            $fid = (int) $fields['fid'];
            if ($fid <= 0 || !$this->supplierExists($fid)) {
                throw new DomainException('Fornitore non trovato.');
            }
            $setParts[] = 'fid = :fid';
            $params['fid'] = $fid;
        }

        if (array_key_exists('password', $fields)) {
            $password = (string) $fields['password'];
            if ($password !== '') {
                $setParts[] = 'password_hash = :password_hash';
                $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }
        }

        if ($setParts === []) {
            throw new DomainException('Nessun campo valido da aggiornare.');
        }

        $statement = $this->pdo->prepare(sprintf('UPDATE Account SET %s WHERE aid = :aid', implode(', ', $setParts)));
        $statement->execute($params);

        $updated = $this->getSupplierAccountById($aid);
        if ($updated === null) {
            throw new DomainException('Account non trovato.');
        }

        return $updated;
    }

    public function deleteAccount(int $aid): bool
    {
        // Verifico che non sia un admin
        $statement = $this->pdo->prepare('SELECT ruolo FROM Account WHERE aid = :aid LIMIT 1');
        $statement->execute(['aid' => $aid]);
        $row = $statement->fetch();

        if ($row === false) {
            return false;
        }

        if ($row['ruolo'] === 'admin') {
            throw new DomainException('Non è possibile eliminare un account admin.');
        }

        $statement = $this->pdo->prepare('DELETE FROM Account WHERE aid = :aid');
        $statement->execute(['aid' => $aid]);

        return $statement->rowCount() > 0;
    }

    private function supplierExists(int $fid): bool
    {
        $statement = $this->pdo->prepare('SELECT fid FROM Fornitori WHERE fid = :fid LIMIT 1');
        $statement->execute(['fid' => $fid]);

        return $statement->fetch() !== false;
    }

    public function createAdminAccount(string $email, string $password): array
    {
        if ($this->emailExists($email)) {
            throw new DomainException('Email già registrata.');
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO Account (email, password_hash, ruolo, fid)
             VALUES (:email, :password_hash, :ruolo, NULL)'
        );
        $statement->execute([
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'ruolo' => 'ADMIN',
        ]);

        $aid = (int) $this->pdo->lastInsertId();
        
        // Return the created admin account
        $statement = $this->pdo->prepare('SELECT aid, email, ruolo, fid FROM Account WHERE aid = :aid');
        $statement->execute(['aid' => $aid]);
        $account = $statement->fetch();
        
        if ($account === false) {
            throw new DomainException('Account non trovato.');
        }

        return [
            'aid' => (int) $account['aid'],
            'email' => $account['email'],
            'ruolo' => $account['ruolo'],
            'fid' => $account['fid'],
        ];
    }

    private function emailExists(string $email, ?int $excludeAid = null): bool
    {
        if ($excludeAid === null) {
            $statement = $this->pdo->prepare('SELECT aid FROM Account WHERE email = :email LIMIT 1');
            $statement->execute(['email' => $email]);
        } else {
            $statement = $this->pdo->prepare('SELECT aid FROM Account WHERE email = :email AND aid != :aid LIMIT 1');
            $statement->execute([
                'email' => $email,
                'aid' => $excludeAid,
            ]);
        }

        return $statement->fetch() !== false;
    }
}
