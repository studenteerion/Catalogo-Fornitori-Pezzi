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

        return $this->getSupplierById($fid);
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
            'SELECT fid, pid, costo
             FROM Catalogo
             ORDER BY fid, pid'
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
    private function getSupplierById(int $fid): array
    {
        $statement = $this->pdo->prepare('SELECT fid, fnome, indirizzo FROM Fornitori WHERE fid = :fid LIMIT 1');
        $statement->execute(['fid' => $fid]);

        $row = $statement->fetch();
        if ($row === false) {
            throw new DomainException('Fornitore non trovato.');
        }

        return $row;
    }

    /** @return array<string,mixed> */
    private function getPartById(int $pid): array
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
            'SELECT fid, pid, costo
             FROM Catalogo
             WHERE fid = :fid AND pid = :pid
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
}
