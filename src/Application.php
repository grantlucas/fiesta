<?php

namespace Fiesta;

use Symfony\Component\Console\Application as ConsoleApplication;

/**
 * Fiesta Application
 *
 * Extend Symfony's console application to add some needed features and data.
 */
class Application extends ConsoleApplication
{
    /**
     * Base directory of the application
     *
     * @var string
     */
    protected $baseDir;

    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {

        // Set the base directory of the application
        $this->baseDir = realpath(__DIR__ . '/..');

        parent::__construct($name, $version);
    }

    /**
     * Get Base Directory
     *
     * @return string The base directory of the Fiesta application
     */
    public function getBaseDir()
    {
        return $this->baseDir;
    }
}
