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

        // Prompt user for confirmation to continue
        $questionHelper = $this->getHelper('question');
        $question = new ConfirmationQuestion('The folling command will overwrite the destination folder. Are you sure you want to continue?', false);

        $output->writeln('');

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

        // Get the list of files from the source folder
        $files = $source->getFiles();

        //TODO: Set up destination's images folder

        // Store which files have been processed
        $processedFiles = array();

        // Loop through files and process them
        foreach ($files as $file) {
            var_dump($file);

            // Store that this file was processed
            $processedFiles[] = $file;

            //TODO: Look for coutnerpart file (markdown text)
            //TODO: If counterpart found, add it to the processed files list to prevent processing it again
            //TODO: Pass the files through Twig partial template which renders the list item
            //TODO: Store the rendered HTML to later passing to page template
            //TODO: Copy the image into the original images folder
            //TODO: Resize the image and copy to small/medium/large images folders

        }

        //TODO: Take list of files (HTML) and pass to page template file
    }
}
