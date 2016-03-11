<?php

namespace Fiesta;

use Lead\Dir\Dir;

class Source
{
    protected $dir;

    /**
     * Constructor
     *
     * @param string $dir Directory to use for the source
     */
    public function __construct($dir)
    {
        // Test if the directory exists and is readable
        if (is_dir($dir) && is_readable($dir)) {
            $this->dir = $dir;
        } else {
            throw new \RuntimeException("Directory must exist and be readable");
        }
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

        $files = Dir::scan($this->dir, [
            'type' => ['file', 'dir', 'readable'],
            'exclude' => ['*.DS_Store'],
            'skipDots' => true,
            'recursive' => false,
        ]);

        return $files;
    }
}
