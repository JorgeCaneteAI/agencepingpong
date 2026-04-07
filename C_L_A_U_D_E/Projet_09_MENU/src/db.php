<?php
/**
 * MealCoach — DB helper (PDO SQLite singleton)
 */

if (!defined('DB_PATH')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Retourne le singleton PDO (WAL mode, foreign keys ON).
 */
function getDb(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA journal_mode = WAL');
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA synchronous = NORMAL');
        } catch (PDOException $e) {
            die('Erreur connexion DB : ' . $e->getMessage());
        }
    }

    return $pdo;
}

/**
 * Executes a query with parameters, returns the PDOStatement.
 */
function query(string $sql, array $params = []): PDOStatement
{
    $stmt = getDb()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Returns all rows from a SELECT query.
 */
function fetchAll(string $sql, array $params = []): array
{
    return query($sql, $params)->fetchAll();
}

/**
 * Returns the first row from a SELECT query (or false).
 */
function fetchOne(string $sql, array $params = []): array|false
{
    return query($sql, $params)->fetch();
}

/**
 * Inserts a row into $table. Returns the last insert ID.
 */
function insert(string $table, array $data): string|false
{
    $cols         = array_keys($data);
    $placeholders = array_map(fn($c) => ':' . $c, $cols);

    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s)',
        $table,
        implode(', ', $cols),
        implode(', ', $placeholders)
    );

    query($sql, $data);
    return getDb()->lastInsertId();
}

/**
 * Updates rows in $table.
 * $where       : SQL fragment after WHERE, e.g. 'id = :id'
 * $whereParams : params for WHERE, e.g. [':id' => 5]
 * Returns the number of affected rows.
 */
function update(string $table, array $data, string $where, array $whereParams = []): int
{
    $setParts = array_map(fn($c) => "$c = :set_$c", array_keys($data));

    $prefixedData = [];
    foreach ($data as $col => $val) {
        $prefixedData[':set_' . $col] = $val;
    }

    $sql = sprintf(
        'UPDATE %s SET %s WHERE %s',
        $table,
        implode(', ', $setParts),
        $where
    );

    $stmt = getDb()->prepare($sql);
    $stmt->execute(array_merge($prefixedData, $whereParams));
    return $stmt->rowCount();
}

/**
 * Gets a setting value from the settings table.
 */
function getSetting(string $cle): string|null
{
    $row = fetchOne('SELECT valeur FROM settings WHERE cle = :cle', [':cle' => $cle]);
    return $row ? $row['valeur'] : null;
}

/**
 * Sets (INSERT OR REPLACE) a setting value.
 */
function setSetting(string $cle, string $valeur): void
{
    query(
        'INSERT OR REPLACE INTO settings (cle, valeur) VALUES (:cle, :valeur)',
        [':cle' => $cle, ':valeur' => $valeur]
    );
}
