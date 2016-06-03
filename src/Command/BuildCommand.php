<?php

namespace Fiesta\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Lead\Dir\Dir as DirHelper;
use League\Container\Container as ServiceContainer;

use Fiesta\Dir;

class BuildCommand extends Command
{
    protected $service;

    public function __construct(ServiceContainer $serviceContainer)
    {
        parent::__construct();

        $this->service = $serviceContainer;
    }

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
        ));

        //TODO: Use Scan to get all image files and optimize them

        //TODO: ********** The following can be made generic, passing in the current folder we're processing **********
        // Get the "current" directory
        $curDirectory = new Dir($destination->getPath());

        // Get list of files to loop through
        $files = $curDirectory->getFiles();

        // Store processed file information for rendering
        $processedFiles = array();

        // Store which files to ignore in processing
        $ignoredFiles = array();

        //TODO: Only proceed if there were files in the folder

        // Loop through the files
        foreach ($files as $file) {
            echo "\nFile: $file";

            // Only proceed if this file isn't ignored
            if (!in_array($file, $ignoredFiles)) {
                var_dump(pathinfo($file));
                $fileInfo = pathinfo($file);

                $currentIsImage = false;
                if (in_array($fileInfo['extension'], array('jpeg', 'jpg', 'png', 'gif'))) {
                    $currentIsImage = true;
                }

                /****** Look for counterpart file (markdown text). ******/
                $counterpartFile = null;
                $markdownExtensions = array(
                    'md',
                    'markdown',
                    'mdown',
                    'mkdn',
                    'mdwn',
                    'mkd',
                );

                foreach ($markdownExtensions as $markdownExtension) {
                    // Only check if we haven't found one yet
                    if (empty($counterpartFile)) {
                        // Build the expected Markdown path based on original file name
                        $markdownFilePath = $fileInfo['dirname'] . '/' . $fileInfo['filename'] . '.' . $markdownExtension;
                        $counterpartFile = $currentIsImage && file_exists($markdownFilePath) ? $markdownFilePath : false;
                    }
                }

                // If counterpart found, add it to the ignored files list to prevent processing it again
                if (!empty($counterpartFile)) {
                    $ignoredFiles[] = $counterpartFile;
                }

                // Add the record to the final array which is eventually passed to Twig
                $processedFiles[$file] = array(
                    'image' => array(
                        'src' => $file,
                        'name' => $fileInfo['filename'],
                    ),
                );

                // If there was a counterpart file, add it to the final array
                if (!empty($counterpartFile)) {
                    var_dump($counterpartFile);
                    $fileContents = file_get_contents($counterpartFile);
                    // Only add the text if the file wasn't empty
                    if ($fileContents != '') {
                        $processedFiles[$file]['text'] = $fileContents;
                    }
                }

                //TODO: Set up a Twig markdown/commonmark filter to process the image text (http://commonmark.thephpleague.com/):w
            }
        }

        print_r($processedFiles);

        //TODO: In folder, create the index.html file rendering the final TWIG output passing processed array to it
        //TODO: THE HARD PART: After we'de done with this folder, get all child folders and perform the same. This should be recursive.
    }

}
