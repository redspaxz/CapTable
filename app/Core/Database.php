<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

class Database
{
    private static ?PDO $pdo = null;
    private static int $txDepth = 0;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $cfg = App::config('db');
            if (($cfg['driver'] ?? 'mysql') === 'sqlite') {
                $dsn = 'sqlite:' . $cfg['sqlite_path'];
                self::$pdo = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                self::$pdo->exec('PRAGMA foreign_keys = ON');
            } else {
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $cfg['host'], $cfg['port'], $cfg['name']);
                self::$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            }
        }
        return self::$pdo;
    }

    /** @return array<int, array<string, mixed>> */
    public static function all(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function scalar(string $sql, array $params = []): mixed
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function lastId(): int
    {
        return (int) self::pdo()->lastInsertId();
    }

    /**
     * Transaction with nesting support: inner calls run inside a
     * SAVEPOINT instead of opening a second transaction (SQLite/MySQL
     * both reject nested beginTransaction).
     */
    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        if (self::$txDepth === 0) {
            $pdo->beginTransaction();
        } else {
            $pdo->exec('SAVEPOINT sp' . self::$txDepth);
        }
        self::$txDepth++;
        try {
            $result = $fn();
            self::$txDepth--;
            if (self::$txDepth === 0) {
                $pdo->commit();
            } else {
                $pdo->exec('RELEASE SAVEPOINT sp' . self::$txDepth);
            }
            return $result;
        } catch (\Throwable $e) {
            self::$txDepth--;
            if (self::$txDepth === 0) {
                $pdo->rollBack();
            } else {
                $pdo->exec('ROLLBACK TO SAVEPOINT sp' . self::$txDepth);
            }
            throw $e;
        }
    }
}
