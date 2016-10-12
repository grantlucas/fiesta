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
use Fiesta\Util;
use Fiesta\Processor;

class BuildCommand extends Command
{
    /**
     * Constructor
     *
     * @param string|null $name
     */
    public function __construct($name = null)
    {
        parent::__construct($name);
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

        /****** THEME is validated by now ******/


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

        /****** Initial destination folder should be good to go ******/

        // Kick off the recursive processing which copies files and builds
        // index.html files
        $processor = new Processor($theme);
        $processor->buildIndexFile($source, $destination);
    }
}
