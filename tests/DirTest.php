<?php

namespace Fiesta;

class DirTest extends \PHPUnit_Framework_TestCase
{
    protected $directory = __DIR__ . '/files/source';

    /**
     * Test directory validation
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage Directory must exist and be readable
     */
    public function testConstructDirValidation()
    {
        $directory = new Dir('does_not_exist', true);
    }

    /**
     * Test directory validation ignoring
     */
    public function testConstructDirIgnoreValidation()
    {
        $directory = new Dir('does_not_exist');

        // Ensure the directory was set
        $this->assertNotEmpty($directory->getPath());
    }

    /**
     * Test construction
     */
    public function testConstruct()
    {
        $directory = new Dir($this->directory);

        // Ensure the directory was set
        $this->assertEquals($this->directory, $directory->getPath());
    }

    /**
     * Test relative path
     *
     * Test to ensure that relative paths get turned into absolute paths
     */
    public function testRelativePath()
    {
        // Relative to the root folder where tests are run from
        $directory = new Dir('tests/files/source');

        $this->assertEquals(__DIR__ . '/files/source', $directory->getPath());
    }

    /**
     * Test file retrieval
     */
    public function testGetFiles()
    {
        $directory = new Dir($this->directory);
        $files = $directory->getFiles();

        $this->assertNotEmpty($files);

        // Assert the various files and directories knowing their sort order
        $this->assertEquals("Boathouse in Winter.jpeg", $this->getFileName($files[0]));
        $this->assertEquals("Boathouse in Winter.md", $this->getFileName($files[1]));
        $this->assertEquals("Single Block of Text.md", $this->getFileName($files[2]));
        $this->assertEquals("Ski Lodge.jpeg", $this->getFileName($files[3]));
    }

    /**
     * Test the creation of a missing folder
     */
    public function testDirectoryCreation()
    {
        $directory = new Dir('tests/files/does_not_exist');

        $this->assertFalse($directory->exists());

        $directory->create();

        $this->assertTrue($directory->exists());

        // Cleanup and delete the created directory
        $directory->delete();
    }

    /**
     * Extract the file name from a file path
     */
    private function getFileName($file)
    {
        $parts = explode("/", $file);

        return array_pop($parts);
    }
}
