<?php

declare(strict_types=1);

namespace App\Application\Repository;

use DomainException;
use PDO;

final class SupplierRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Restituisce il catalogo di un fornitore con i dettagli dei pezzi
     * @return array<int,array<string,mixed>>
     */
    public function getSupplierCatalog(int $fid): array
    {
        $statement = $this->pdo->prepare(
            'SELECT c.fid, c.pid, c.costo, p.pnome, p.colore
             FROM Catalogo c
             INNER JOIN Pezzi p ON c.pid = p.pid
             WHERE c.fid = :fid
             ORDER BY p.pnome'
        );
        $statement->execute(['fid' => $fid]);

        return $statement->fetchAll();
    }

    /**
     * Lista tutti i pezzi disponibili
     * @return array<int,array<string,mixed>>
     */
    public function listAllParts(): array
    {
        return $this->pdo->query('SELECT pid, pnome, colore FROM Pezzi ORDER BY pnome')->fetchAll();
    }

    /**
     * Crea un nuovo pezzo
     * @return array<string,mixed>
     */
    public function createPart(string $name, string $color): array
    {
        $statement = $this->pdo->prepare('INSERT INTO Pezzi (pnome, colore) VALUES (:pnome, :colore)');
        $statement->execute([
            'pnome' => $name,
            'colore' => $color,
        ]);

        return $this->getPartById((int) $this->pdo->lastInsertId());
    }

    /**
     * Aggiunge un pezzo al catalogo del fornitore
     * @return array<string,mixed>
     */
    public function addToCatalog(int $fid, int $pid, float $costo): array
    {
        // Verifica che il pezzo esista
        $part = $this->getPartById($pid);
        if ($part === null) {
            throw new DomainException('Pezzo non trovato.');
        }

        // Verifica che non esista già nel catalogo
        $existing = $this->getCatalogItem($fid, $pid);
        if ($existing !== null) {
            throw new DomainException('Questo pezzo è già presente nel tuo catalogo.');
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO Catalogo (fid, pid, costo) VALUES (:fid, :pid, :costo)'
        );
        $statement->execute([
            'fid' => $fid,
            'pid' => $pid,
            'costo' => $costo,
        ]);

        $item = $this->getCatalogItem($fid, $pid);
        if ($item === null) {
            throw new DomainException('Errore durante l\'inserimento nel catalogo.');
        }

        return $item;
    }

    /**
     * Aggiorna il costo di un pezzo nel catalogo
     * @return array<string,mixed>
     */
    public function updateCatalogItem(int $fid, int $pid, float $costo): array
    {
        $statement = $this->pdo->prepare(
            'UPDATE Catalogo
             SET costo = :costo
             WHERE fid = :fid AND pid = :pid'
        );
        $statement->execute([
            'costo' => $costo,
            'fid' => $fid,
            'pid' => $pid,
        ]);

        if ($statement->rowCount() === 0) {
            throw new DomainException('Elemento del catalogo non trovato.');
        }

        $item = $this->getCatalogItem($fid, $pid);
        if ($item === null) {
            throw new DomainException('Errore durante l\'aggiornamento.');
        }

        return $item;
    }

    /**
     * Rimuove un pezzo dal catalogo del fornitore
     */
    public function removeFromCatalog(int $fid, int $pid): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM Catalogo WHERE fid = :fid AND pid = :pid');
        $statement->execute([
            'fid' => $fid,
            'pid' => $pid,
        ]);

        return $statement->rowCount() > 0;
    }

    // ==================== Helper Methods ====================

    /**
     * Ottiene un pezzo per ID
     * @return array<string,mixed>|null
     */
    private function getPartById(int $pid): ?array
    {
        $statement = $this->pdo->prepare('SELECT pid, pnome, colore FROM Pezzi WHERE pid = :pid LIMIT 1');
        $statement->execute(['pid' => $pid]);

        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Ottiene un elemento del catalogo con dettagli del pezzo
     * @return array<string,mixed>|null
     */
    private function getCatalogItem(int $fid, int $pid): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT c.fid, c.pid, c.costo, p.pnome, p.colore
             FROM Catalogo c
             INNER JOIN Pezzi p ON c.pid = p.pid
             WHERE c.fid = :fid AND c.pid = :pid
             LIMIT 1'
        );
        $statement->execute([
            'fid' => $fid,
            'pid' => $pid,
        ]);

        $row = $statement->fetch();
        return $row === false ? null : $row;
    }
}
