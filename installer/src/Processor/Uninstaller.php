<?php

/**
 * Part of the Antares Project package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.
 *
 * @package    Antares Core
 * @version    0.9.0
 * @author     Original Orchestral https://github.com/orchestral
 * @author     Antares Team
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017, Antares Project
 * @link       http://antaresproject.io
 */


namespace Antares\Installation\Processor;

use Illuminate\Database\Connection;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Session\Store as Session;
use Illuminate\Log\Writer as Logger;
use Antares\Installation\Contracts\UninstallListener;
use Exception;

class Uninstaller {

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Kernel
     */
    protected $kernel;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Uninstaller constructor.
     * 
     * @param Connection $connection
     * @param Kernel $kernel
     * @param Session $session
     * @param Logger $logger
     */
    public function __construct(Connection $connection, Kernel $kernel, Session $session, Logger $logger) {
        $this->connection   = $connection;
        $this->kernel       = $kernel;
        $this->session      = $session;
        $this->logger       = $logger;
    }

    /**
     * Flush cache and session.
     * 
     * @param UninstallListener $listener
     */
    public function flushCacheAndSession(UninstallListener $listener) {
        try {
            $this->kernel->call('cache:clear');
            $this->kernel->call('view:clear');
            $this->session->flush();

            $listener->uninstallSuccess('Cache and session has been flushed successfully.');
        }
        catch(Exception $e) {
            $this->logger->emergency($e->getMessage());
            $listener->uninstallFailed($e->getMessage());
        }

    }

    /**
     * Truncate all tables from the application database.
     * 
     * @param UninstallListener $listener
     * @throws Exception
     */
    public function truncateTables(UninstallListener $listener) {
        $this->connection->beginTransaction();
        $this->connection->statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            $tableNames = $this->connection->getDoctrineSchemaManager()->listTableNames();

            foreach($tableNames as $tableName) {
                $this->connection->table($tableName)->truncate();
            }

            $this->connection->statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->connection->commit();

            $listener->uninstallSuccess('Database tables has been truncated successfully.');
        }
        catch(Exception $e) {
            $this->connection->rollBack();
            $this->logger->emergency($e->getMessage());
            $listener->uninstallFailed($e->getMessage());
        }
    }
    
}
