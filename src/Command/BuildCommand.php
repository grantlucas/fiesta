<?php

namespace Fiesta\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Lead\Dir\Dir as DirHelper;

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

        $output->writeln('<info>Building Site</info>');
        $output->writeln('');
        $output->writeln('Source:' . $source->getPath());
        $output->writeln('Destination:' . $destination->getPath());

        // Prompt user for confirmation to continue
        $questionHelper = $this->getHelper('question');
        $question = new ConfirmationQuestion('<question>The folling command will overwrite the destination folder. Are you sure you want to continue?</question>', false);

        $output->writeln('');

        if (!$questionHelper->ask($input, $output, $question)) {
            return;
        }

        $output->writeln('<info>Continuing with Build command</info>');

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

        // Copy entire source to destination with callbacks
        //TODO: Get it to ignore .DS_Store files etc: https://github.com/crysalead/dir/issues/1
        DirHelper::copy($source->getPath(), $destination->getPath(), array(
            'mode' => 0700,
            'childsOnly' => true,
            'recursive' => true,
            'fileCopiedCallback' => 'Fiesta\Command\BuildCommand::fileCopied',
        ));

        //TODO: Resize and optimize the copied file on callback if it's an image

        //TODO: ********** The following can be made generic, passing in the current folder we're processing **********
        // Get the "current" directory
        $curDirectory = new Dir($destination->getPath());

        //TODO: Loop through files in first level of the destination folder
        $files = $curDirectory->getFiles();

        $processedFiles = [];

        foreach ($files as $file) {
            echo "\nFile: $file";
            //TODO: Look for coutnerpart file (markdown text)
            //TODO: If counterpart found, add it to the processed files list to prevent processing it again
            //TODO: Pass the files through Twig partial template which renders the list item
            //TODO: Store the rendered HTML to later passing to page template
            //TODO: Remove the file or counterpart file if it was not an image
        }

        //TODO: In folder, create the index.html file rendering the final TWIG output
        //TODO: THE HARD PART: After we'de done with this folder, get all child folders and perform the same. This should be recursive.
    }

    /**
     * File copied callback
     */
    public static function fileCopied($path)
    {
        echo "\nFile Copied: $path";
    }
}
