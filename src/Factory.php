<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * (L) Licensed <https://opensource.org/license/MIT>
 *
 * (A) zhiqiang <https://www.zhiqiang.wang>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup;

use think\App;
use think\db\ConnectionInterface;
use tp5er\Backup\reader\ReaderInterface;
use tp5er\Backup\writer\WriterInterface;

class Factory
{
    /**
     * @var App
     */
    protected $app;
    /**
     * @var BackupInterface
     */
    protected $backup;
    /**
     * @var ConnectionInterface
     */
    protected $connection;
    /**
     * @var WriterInterface
     */
    protected $writer;
    /**
     * @var ReaderInterface
     */
    protected $reader;

    public function __construct(
        App                 $app,
        BackupInterface     $backup,
        ConnectionInterface $connection,
        WriterInterface     $writer,
        ReaderInterface     $reader,
        $config
    ) {
        $this->app = $app;
        $this->backup = $backup;
        $this->connection = $connection;
        $this->writer = $writer;
        $this->reader = $reader;
        $this->config = $config;
    }

    /**
     * @return App
     */
    public function getApp(): App
    {
        return $this->app;
    }

    /**
     * @return BackupInterface
     */
    public function getBackup(): BackupInterface
    {
        return $this->backup;
    }

    /**
     * @return ConnectionInterface
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * @return WriterInterface
     */
    public function getWriter(): WriterInterface
    {
        $this->writer->setApp($this->app);
        $this->writer->setBackup($this->getBackup());
        $this->writer->setConfig($this->config);

        return $this->writer;
    }

    /**
     * @return ReaderInterface
     */
    public function getReader(): ReaderInterface
    {
        $this->reader->setApp($this->app);
        $this->reader->setConnection($this->getConnection());
        $this->reader->setConfig($this->config);

        return $this->reader;
    }

}
