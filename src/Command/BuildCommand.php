<?php

namespace Fiesta\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use Fiesta\Dir;

class BuildCommand extends Command
{
    protected function configure()
    {
        $this->setName('build')
        ->setDescription('Build your Fiesta photo essay.')
        ->addArgument('source',
            InputArgument::REQUIRED,
            'The source folder with your original images.'
        )
        ->addArgument('destination',
            InputArgument::REQUIRED,
            'The destination folder where the site will be built.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = new Dir($input->getArgument('source'), true);
        $destination = new Dir($input->getArgument('destination'));

        $output->writeln('Building Site');
        $output->writeln('Source:' . $source->getDir());
        $output->writeln('Destination:' . $destination->getDir());

        //TODO: Prompt user for confirmation to continue
        $questionHelper = $this->getHelper('question');
        $question = new ConfirmationQuestion('The folling command will overwrite the destination folder. Are you sure you want to continue?', false);

        if (!$questionHelper->ask($input, $output, $question)) {
            return;
        }

        $output->writeln('Continuing with Build command');

        // Delete the destination to clear it
        if ($destination->exists()) {
            $destination->delete();
        }

        // Create the destination directory
        $destination->create();

        // Ensure the destination is writable
        if (!$destination->isWritable()) {
            throw new \RuntimeException("Destination directory is not writable");
        }
    }
}
