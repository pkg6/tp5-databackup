<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup;

use ReflectionClass;
use ReflectionProperty;
use think\contract\Arrayable;
use think\contract\Jsonable;

class FileInfo implements Arrayable, Jsonable
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $database;
    /**
     * @var string
     */
    public $connection;
    /**
     * @var string
     */
    public $filename;

    /**
     * @var mixed
     */
    public $system_name;
    /**
     * @var string
     */
    public $write_type;
    /**
     * @var string
     */
    public $write_class;
    /**
     * @var int
     */
    public $size;

    /**
     * @param \SplFileInfo $file
     * @param FileName $fileName
     */
    public function __construct(\SplFileInfo $file, FileName $fileName)
    {
        list($database, $connection_name, $extension, $timeExt) = $fileName->fileNameDatabaseConnectionNameExt($file);
        $exts = $fileName->manager->writes;
        if (isset($exts[$extension])) {
            $this->name = $file->getFilename();
            $this->database = $database;
            $this->connection = $connection_name;
            $this->filename = $file->getPathname();
            $this->system_name = $timeExt;
            $this->write_type = $extension;
            $this->write_class = $exts[$extension];
            $this->size = format_bytes($file->getSize());

        }
    }

    /**
     * @return string[]
     */
    public static function Properties()
    {
        $properties = [];
        $ref = new ReflectionClass(FileInfo::class);
        foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $properties[] = $property->name;
        }

        return $properties;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $properties = self::Properties();
        $ret = [];
        foreach ($properties as $property) {
            $ret[$property] = $this->$property;
        }
        return $ret;
    }

    /**
     * @param int $options
     *
     * @return string
     */
    public function toJson(int $options = JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->toArray());
    }
}
