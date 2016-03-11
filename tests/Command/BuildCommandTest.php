<?php

namespace Fiesta\Command;

use Symfony\Component\Console\Application;
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
     * Test an execution with all arguments
     */
    public function testExecute()
    {
        $application = new Application();
        $application->add(new BuildCommand());

        $command = $application->find('build');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source' => '/tmp/1',
            'destination' => '/tmp/2',
        ]);

        $this->assertRegExp("/Building Site/", $commandTester->getDisplay());
    }
}
