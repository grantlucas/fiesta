<?php

namespace Fiesta\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Lead\Dir\Dir as DirHelper;
use Aptoma\Twig\Extension\MarkdownExtension;
use Aptoma\Twig\Extension\MarkdownEngine;
use Twig_Environment;
use Twig_Loader_Filesystem;
use Mni\FrontYAML\Parser as MarkdownParser;

use Fiesta\Dir;
use Fiesta\Util;

class BuildCommand extends Command
{
    /**
     * @var MarkdownParser
     *
     * Markdown parser responsible for returning FrontMatter YAML and
     * Markdown content
     */
    protected $markdownParser;

    /**
     * Constructor
     *
     * @param string|null $name
     */
    public function __construct($name = null)
    {
        parent::__construct($name);

        // Set up the markdown parser
        $this->markdownParser = new MarkdownParser();
    }

    /**
     * Configure
     */
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
            )
            ->addOption('theme-dir',
                null,
                InputOption::VALUE_REQUIRED,
                'Where are themes located? Defaults to the themes folder contained within the Fiesta source. It can be a relative or absolute folder path.'
            )
            ->addOption('theme',
                null,
                InputOption::VALUE_REQUIRED,
                'What theme should be used?',
                'Standard'
            );
    }

    /**
     * Execute
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //TODO: set up manifest file for themes for what extra files should be copied into the folder, such as styles and Javascript. It'd be good to have the CSS and JS in only the top level btw

        // Set up the theme directory
        $themeDirectory = $input->getOption('theme-dir');

        // If themes directory option is empty, use default
        if (!$themeDirectory) {
            $themeDirectory = Util::appendToPath($this->getApplication()->getBaseDir(), 'themes');
        }

        // Build the specific theme directory using the theme name
        $theme = new Dir(Util::appendToPath($themeDirectory, $input->getOption('theme')));

        // Ensure the theme folder exists and is readable
        if (!$theme->exists() || !$theme->isReadable()) {
            throw new \RuntimeException("The chosen Theme folder (" . $theme->getPath() . ") doesn't exist or isn't readable");
        }

        // Validate the needed base Twig file exists and is readable
        $twigBaseFile = 'base.html.twig';
        $twigBaseFilePath = Util::appendToPath($theme->getPath(), $twigBaseFile);
        if (!is_file($twigBaseFilePath) || !is_readable($twigBaseFilePath)) {
            throw new \RuntimeException("The required Twig base file (" . $twigBaseFile . "), does not exist in the theme folder or is not readable.");
        }


        // Set up Twig template loader using the theme folder
        $twigLoader = new Twig_Loader_Filesystem($theme->getPath());

        // Set up the twig environment using the theme's folder
        $twig = new Twig_Environment($twigLoader);
        //TODO: Add caching and way to clear cache

        /*
         * TODO: This processing *could* also be done in this script and
         * instead pass HTML to the view. That way themes are reliant on
         * calling `| markdown` when rendering the text
         */
        // Add markdown extension
        $twig->addExtension(new MarkdownExtension(new MarkdownEngine\PHPLeagueCommonMarkEngine()));



        /****** Begin with processing ******/

        $source = new Dir($input->getArgument('source'), true);
        $destination = new Dir($input->getArgument('destination'));

        $output->writeln('<info>Building Site</info>');
        $output->writeln('');
        $output->writeln('Source:' . $source->getPath());
        $output->writeln('Destination:' . $destination->getPath());

        // Prompt user for confirmation to continue
        $questionHelper = $this->getHelper('question');
        $question = new ConfirmationQuestion('<question>The folling command will overwrite the destination folder. Are you sure you want to continue? (y/N)</question>', false);

        $output->writeln('');

        if (!$questionHelper->ask($input, $output, $question)) {
            $output->writeln('<info>Aborting the Build command</info>');
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

        // Initialize supported image and markdown extensions
        $imageExtensions = array(
            'jpeg',
            'jpg',
            'png',
            'gif',
        );

        $markdownExtensions = array(
            'md',
            'markdown',
            'mdown',
            'mkdn',
            'mdwn',
            'mkd',
        );

        // Initialize group counter
        $groupCounter = 0;

        // Loop through the files
        foreach ($files as $file) {
            echo "\nFile: $file";

            // Only proceed if this file isn't ignored
            if (!in_array($file, $ignoredFiles)) {
                var_dump(pathinfo($file));
                $fileInfo = pathinfo($file);

                // Determine if the current file is an image or a markdown file
                $currentFileType = null;
                if (in_array($fileInfo['extension'], $imageExtensions)) {
                    $currentFileType = 'image';
                } elseif (in_array($fileInfo['extension'], $markdownExtensions)) {
                    $currentFileType = 'markdown';
                }

                // Initialize file variables
                $counterpartFile = null;
                $processedFile = null;

                // Store whether a file being processed should be in its own group
                $groupBreak = false;

                // If it's an image, add it to the final array and look for counter part file
                if ($currentFileType == 'image') {
                    // Add the image data to the final array which is eventually passed to Twig
                    $processedFile = array(
                        'image' => array(
                            'src' => $file,
                            'name' => $fileInfo['filename'],
                        ),
                    );

                    /****** Look for counterpart file (markdown text). ******/
                    foreach ($markdownExtensions as $markdownExtension) {
                        // Only check if we haven't found one yet
                        if (empty($counterpartFile)) {
                            // Build the expected Markdown path based on original file name
                            $markdownFilePath = $fileInfo['dirname'] . '/' . $fileInfo['filename'] . '.' . $markdownExtension;
                            $counterpartFile = file_exists($markdownFilePath) ? $markdownFilePath : false;
                        }
                    }

                    // If a counterpart is found, add it to the ignored files list to prevent processing it again
                    if (!empty($counterpartFile)) {
                        $ignoredFiles[] = $counterpartFile;
                    }

                    // If there was a counterpart file, add it to the final array
                    if (!empty($counterpartFile)) {
                        var_dump($counterpartFile);

                        // Parse the markdown file with potential YAML front matter
                        $parsedFile = $this->markdownParser->parse(file_get_contents($counterpartFile), false);

                        // Get any image settings from the YAML front matter
                        $imageSettings = $parsedFile->getYAML();
                        var_dump($imageSettings);

                        // Add image settings
                        $processedFile['image']['settings'] = $imageSettings ?: array();

                        // Full width images are their own group
                        if ($imageSettings && isset($imageSettings['image-layout']) && $imageSettings['image-layout'] == 'full-width') {
                            $groupBreak = true;
                        }

                        // Only add the text if the content wasn't empty
                        if ($parsedFile->getContent() != '') {
                            $processedFile['text'] = $parsedFile->getContent();

                            // Image file + text will be it's own group
                            $groupBreak = true;
                        }
                    }
                } elseif ($currentFileType == 'markdown') {
                    // Look for counterpart image file
                    foreach ($imageExtensions as $imageExtension) {
                        // Only check if we haven't found one yet
                        if (empty($counterpartFile)) {
                            // Build the expected image path based on original file name
                            $imageFilePath = $fileInfo['dirname'] . '/' . $fileInfo['filename'] . '.' . $imageExtension;
                            $counterpartFile = file_exists($imageFilePath) ? $imageFilePath : false;
                        }
                    }

                    // Only proceed if there ISN'T a counterpart file. It'll be dealt with when its imag is dealt with
                    if (!$counterpartFile) {
                        // With no counterpart file found, treat this as a single text block
                        // Parse the file
                        $parsedFile = $this->markdownParser->parse(file_get_contents($file), false);

                        // Add the text block to the page
                        $processedFile = array(
                            'text' => $parsedFile->getContent(),
                        );

                        // Single text files are their own group
                        $groupBreak = true;
                    }
                }

                // If we have a group break, increment the counter
                if ($groupBreak) {
                    $groupCounter++;
                }

                //  Add the final image and/or text file to the processed files array
                $processedFiles[$groupCounter][$file] = $processedFile;

                // If this was a single file for this group, increment the
                // group counter once again to ensure the next file being
                // processed doesn't get lumped into this group
                if ($groupBreak) {
                    $groupCounter++;
                }

                //TODO: Remove markdown files

                //TODO: If the image is full width, add a group class
            }
        }

        print_r($processedFiles);

        // Load the template's base file
        $baseTemplate = $twig->loadTemplate($twigBaseFile);

        //TODO: Figure out a title
        //TODO: Figure out description for page
        //TODO: Pass in child menu
        $baseHtml = $baseTemplate->render(array(
            'fileGroups' => $processedFiles,
        ));

        print_r($baseHtml);
        print_r($curDirectory->getPath());

        // Create the index.html file with the rendered HTML
        file_put_contents($curDirectory->getPath() . '/index.html', $baseHtml);

        //TODO: THE HARD PART: After we'de done with this folder, get all child folders and perform the same. This should be recursive.
    }

}
