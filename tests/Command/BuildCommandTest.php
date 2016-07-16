<?php

namespace Fiesta\Command;

use Fiesta\Application;
use Symfony\Component\Console\Tester\CommandTester;

class BuildCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test exceptions for missing both arguments
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage Not enough arguments (missing: "source, destination")
     */
    public function testExecuteMissingArguments()
    {
        $application = new Application();
        $application->add(new BuildCommand());

        $command = $application->find('build');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }

    /**
     * Test exception for missing destination argument
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage Not enough arguments (missing: "destination")
     */
    public function testExecuteMissingDestArgument()
    {
        $application = new Application();
        $application->add(new BuildCommand());

        $command = $application->find('build');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source' => '/tmp',
        ]);
    }

    /**
     * Test exception for missing source argument
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage Not enough arguments (missing: "source")
     */
    public function testExecuteMissingSourceArgument()
    {
        $application = new Application();
        $application->add(new BuildCommand());

        $command = $application->find('build');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'destination' => '/tmp',
        ]);
    }

    /**
     * Test abort execution
     */
    public function testAbortExecute()
    {
        $application = new Application();
        $application->add(new BuildCommand());

        $command = $application->find('build');

        $helper = $command->getHelper('question');
        $helper->setInputStream($this->getInputStream('n\\n'));

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source' => 'tests/files/source',
            'destination' => '/tmp/2',
        ]);

        $this->assertRegExp("/Building Site/", $commandTester->getDisplay());
        $this->assertRegExp("/Aborting the Build command/", $commandTester->getDisplay());
    }

    /**
     * Test an execution with all arguments
     */
    public function testExecute()
    {
        $application = new Application();
        $application->add(new BuildCommand());

        $command = $application->find('build');

        $helper = $command->getHelper('question');
        $helper->setInputStream($this->getInputStream('y\\n'));

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source' => 'tests/files/source',
            'destination' => '/tmp/2',
        ]);

        $this->assertRegExp("/Building Site/", $commandTester->getDisplay());
        $this->assertRegExp("/Continuing with Build command/", $commandTester->getDisplay());
    }

    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);
        rewind($stream);

        return $stream;
    }
}
