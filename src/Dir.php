<?php

namespace Fiesta;

use Lead\Dir\Dir as DirHelper;

class Dir
{
    protected $dir;

    /**
     * Constructor
     *
     * @param string $dir Directory to use for the source
     * @param bool $requireExists Boolean for whether or not to check if the directory exists already
     */
    public function __construct($dir, $requireExists = false)
    {
        // Make the path absolute
        $dir = $this->makePathAbsolute($dir);

        // Test if the directory exists and is readable
        if ($requireExists && (!is_dir($dir) || !is_readable($dir))) {
            throw new \RuntimeException("Directory must exist and be readable");
        }

        $this->dir = $dir;
    }

    /**
     * Get Directory
     *
     * @return string Directory path for this object
     */
    public function getDir()
    {
        return $this->dir;
    }

    /**
     * Get Files
     *
     * @return array List of files in the source directory
     */
    public function getFiles()
    {

        $files = DirHelper::scan($this->dir, [
            'type' => ['file', 'dir', 'readable'],
            'exclude' => ['*.DS_Store'],
            'skipDots' => true,
            'recursive' => false,
        ]);

        return $files;
    }

    /**
     * Make Path Absolute
     *
     * @param string $path
     *
     * @return string Full absolute path
     */
    protected function makePathAbsolute($path)
    {
        if (substr($path, 0, 1) != "/") {
            // Make the string absolute
            $path = getcwd() . "/" . $path;
        }

        return $path;
    }
}
