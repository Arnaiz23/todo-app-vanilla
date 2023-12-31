<?php declare(strict_types=1);

namespace Lkrms\Store\Concept;

use Lkrms\Contract\IFacade;
use Lkrms\Contract\ReceivesFacade;
use Lkrms\Facade\Sys;
use Lkrms\Utility\File;
use RuntimeException;
use SQLite3;
use Throwable;

/**
 * Base class for SQLite-backed stores
 */
abstract class SqliteStore implements ReceivesFacade
{
    /**
     * @var SQLite3|null
     */
    private $Db;

    /**
     * @var string|null
     */
    private $Filename;

    /**
     * @var bool
     */
    private $IsTransactionOpen;

    /**
     * @var class-string<IFacade<static>>|null
     */
    private $Facade;

    /**
     * @var bool
     */
    private $CheckIsRunning = false;

    /**
     * @inheritDoc
     */
    final public function setFacade(string $name)
    {
        $this->Facade = $name;

        return $this;
    }

    /**
     * Create or open a database
     *
     * @return $this
     * @throws RuntimeException if a database is already open.
     */
    final protected function openDb(string $filename, ?string $query = null)
    {
        if ($this->Db) {
            throw new RuntimeException('Database already open');
        }

        if ($filename !== ':memory:') {
            File::create($filename, 0600, 0700);
        }

        $db = new SQLite3($filename);
        $db->enableExceptions();
        $db->busyTimeout(60000);
        $db->exec('PRAGMA journal_mode=WAL');
        $db->exec('PRAGMA foreign_keys=ON');
        if ($query) {
            $db->exec($query);
        }
        $this->Db = $db;
        $this->Filename = $filename;

        return $this;
    }

    /**
     * If a database is open, close it
     *
     * @return $this
     */
    final protected function closeDb(bool $unloadFacade = true)
    {
        try {
            if (!$this->Db) {
                return $this;
            }

            $this->Db->close();
            $this->Db = null;
            $this->Filename = null;

            return $this;
        } finally {
            if ($unloadFacade && $this->Facade) {
                [$this->Facade, 'unload']();
                $this->Facade = null;
            }
        }
    }

    /**
     * Close the database
     *
     * @return $this
     */
    public function close()
    {
        return $this->closeDb();
    }

    /**
     * Override to perform an action whenever the open SQLite3 instance is
     * accessed
     *
     * Called once per call to {@see SqliteStore::db()}.
     *
     * @return $this
     */
    protected function check()
    {
        return $this;
    }

    /**
     * Check if a database is open
     */
    final public function isOpen(): bool
    {
        return $this->Db ? true : false;
    }

    /**
     * Get the filename of the database
     */
    final public function getFilename(): ?string
    {
        return $this->Filename;
    }

    /**
     * True if check() is currently running
     *
     * @return bool
     */
    final protected function isCheckRunning(): bool
    {
        return $this->CheckIsRunning;
    }

    /**
     * Call from check() and return if isCheckRunning() returns false
     *
     * Recommended if `check()` has callers other than {@see SqliteStore::db()}.
     *
     * @return $this
     */
    final protected function safeCheck()
    {
        $this->CheckIsRunning = true;
        try {
            return $this->check();
        } finally {
            $this->CheckIsRunning = false;
        }
    }

    /**
     * Get the open SQLite3 instance
     *
     * @throws RuntimeException if no database is open.
     */
    final protected function db(): SQLite3
    {
        if ($this->Db) {
            if ($this->CheckIsRunning) {
                return $this->Db;
            }

            return $this->safeCheck()->Db;
        }

        throw new RuntimeException('No database open');
    }

    final protected function isTransactionOpen(): bool
    {
        return $this->Db && $this->IsTransactionOpen;
    }

    /**
     * BEGIN a transaction
     *
     * @return $this
     * @throws RuntimeException if a transaction is already open.
     */
    final protected function beginTransaction()
    {
        if ($this->Db && $this->IsTransactionOpen) {
            throw new RuntimeException('Transaction already open');
        }

        $this->db()->exec('BEGIN');
        $this->IsTransactionOpen = true;

        return $this;
    }

    /**
     * COMMIT a transaction
     *
     * @return $this
     * @throws RuntimeException if no transaction is open.
     */
    final protected function commitTransaction()
    {
        if ($this->Db && !$this->IsTransactionOpen) {
            throw new RuntimeException('No transaction open');
        }

        $this->db()->exec('COMMIT');
        $this->IsTransactionOpen = false;

        return $this;
    }

    /**
     * ROLLBACK a transaction
     *
     * @param bool $ignoreNoTransaction If `true` and no transaction is open,
     * return without throwing an exception. Recommended in `catch` blocks where
     * a transaction may or may not have been successfully opened.
     * @return $this
     * @throws RuntimeException if no transaction is open.
     */
    final protected function rollbackTransaction(bool $ignoreNoTransaction = false)
    {
        if ($this->Db && !$this->IsTransactionOpen) {
            if ($ignoreNoTransaction) {
                return $this;
            }

            throw new RuntimeException('No transaction open');
        }

        $this->db()->exec('ROLLBACK');
        $this->IsTransactionOpen = false;

        return $this;
    }

    /**
     * BEGIN a transaction, run a callback and COMMIT or ROLLBACK as needed
     *
     * A rollback is attempted if an exception is caught, otherwise the
     * transaction is committed.
     *
     * @return mixed The callback's return value.
     * @throws RuntimeException if a transaction is already open.
     */
    final protected function callInTransaction(callable $callback)
    {
        if ($this->Db && $this->IsTransactionOpen) {
            throw new RuntimeException('Transaction already open');
        }

        $this->beginTransaction();

        try {
            $result = $callback();
            $this->commitTransaction();
        } catch (Throwable $ex) {
            $this->rollbackTransaction();

            throw $ex;
        }

        return $result;
    }

    /**
     * Throw an exception if the SQLite3 library doesn't support UPSERT syntax
     *
     * @return $this
     */
    final protected function requireUpsert()
    {
        if (Sys::sqliteHasUpsert()) {
            return $this;
        }

        throw new RuntimeException('SQLite 3.24 or above required');
    }
}
