<?php

namespace Fiesta;

use Lead\Dir\Dir as DirHelper;
use Fiesta\Util;

class Dir
{
    protected $dir;

    /**
     * Constructor
     *
     * @param string $dir Directory to use for the source
     * @param bool $requireExists Boolean for whether or not to check if the directory exists already
     *
     * @return Dir Instance of this directory
     */
    public function __construct($dir, $requireExists = false)
    {
        // Make the path absolute
        $this->dir = Util::makePathAbsolute($dir);

        // Test if the directory exists and is readable
        if ($requireExists && (!$this->exists() || !$this->isReadable())) {
            throw new \RuntimeException("Directory must exist and be readable");
        }

        return $this;
    }

    /**
     * Get Path
     *
     * @return string Path for this object
     */
    public function getPath()
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
            'type' => ['file'],
            'exclude' => ['*.DS_Store'],
            'skipDots' => true,
            'recursive' => false,
        ]);

        return $files;
    }

    /**
     * Test if the current directory exists
     *
     * @return bool
     */
    public function exists()
    {
        return is_dir($this->dir);
    }

    /**
     * Test if the current directory is readable
     *
     * @return bool
     */
    public function isReadable()
    {
        return is_readable($this->dir);
    }

    /**
     * Test if the current directory is writable
     *
     * @return bool
     */
    public function isWritable()
    {
         return is_writable($this->dir);
    }

    /**
     * Create directory
     */
    public function create()
    {
        // Create directory only accessible by the current user
        DirHelper::make($this->dir, [
            'mode' => 0700,
            'recursive' => true,
        ]);

        if (!is_dir($this->dir)) {
            throw new \RuntimeException("Unable to create directory at: " . $this->dir);
        }
    }

    /**
     * Delete Directory
     */
    public function delete()
    {
        DirHelper::remove($this->dir);

        // Check if it still exists
        if (is_dir($this->dir)) {
            throw new \RuntimeException("Unable to delete directory at: " . $this->dir);
        }
    }
}
