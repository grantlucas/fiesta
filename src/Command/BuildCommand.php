<?php

namespace Fiesta\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends Command
{
    protected function configure()
    {
        $this->setName('build')
        ->setDescription('Build your Fiesta photo essay.')
        ->addArgument('source',
            InputOption::VALUE_REQUIRED,
            'The source folder with your original images.'
        )
        ->addArgument('destination',
            InputOption::VALUE_REQUIRED,
            'The destination folder where the site will be built.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
            $input->validate();
        /*try {
            $input->validate();
        } catch (Exception $e) {
            $output->writeln($e->getMessage());
        }*/

        $source = $input->getArgument('source');
        $destination = $input->getArgument('destination');
        var_dump($source);
        var_dump($destination);

        /*if (empty($source)) {
            throw new \InvalidArgumentException('Source required');
        }

        if (empty($destination)) {
            throw new \InvalidArgumentException('Destination required');
        }
*/
        $output->writeln('Build that site');
        $output->writeln('Source: ' . $source);
        $output->writeln('Destination: ' . $destination);
    }
}
