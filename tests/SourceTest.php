<?php

namespace Fiesta;

class SourceTest extends \PHPUnit_Framework_TestCase
{
    protected $sourceDir = __DIR__ . '/files/source';

    /**
     * Test directory validation
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage Directory must exist and be readable
     */
    public function testConstructDirValidation()
    {
        $source = new Source(__DIR__ . '/does_not_exist');
    }

    /**
     * Test construction
     */
    public function testConstruct()
    {
        $source = new Source($this->sourceDir);

        // Ensure the directory was set
        $this->assertEquals($this->sourceDir, $source->getDir());

        // Store the source to later usage
        $this->source = $source;
    }

    /**
     * Test file retrieval
     *
     * @TODO: Write/sketch out the workflow for how we move through directories, copying as we go
     * @depends testConstruct
     */
    public function testGetFiles()
    {
        $source = new Source($this->sourceDir);
        $files = $source->getFiles();

        $this->assertNotEmpty($files);

        // Assert the various files and direcotries knowing their sort order
        $this->assertEquals("Boathouse in Winter.jpeg", $this->getFileName($files[0]));
        $this->assertTrue(is_dir($files[1]));
        $this->assertEquals("Ski Lodge.jpeg", $this->getFileName($files[2]));
        $this->assertTrue(is_dir($files[3]));
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
