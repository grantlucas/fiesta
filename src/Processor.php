<?php

namespace Fiesta;

use Mni\FrontYAML\Parser as MarkdownParser;
use Lead\Dir\Dir as DirHelper;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Class Processor
 *
 * Perform recursive processing in order to generate index files.
 */
class Processor
{
    /**
     * @var MarkdownParser
     *
     * Markdown parser responsible for returning FrontMatter YAML and
     * Markdown content
     */
    protected $markdownParser;

    /**
     * @var ThemeDir
     *
     * Theme directory to work with
     */
    protected $themeDir;

    /**
     * @var Twig
     *
     * Instance of the Twig environment
     */
    protected $twig;

    /**
     * @var Twig base file
     */
    protected $twigBaseFile = 'page.html.twig';

    /**
     * @var Manifest
     *
     * Theme manifest information
     */
    protected $themeManifest;

    /**
     * Constructor
     *
     * @param \Twig_Environment $twig
     * @param Dir $themeDir Base folder of the theme
     * @param string $themeManifest Directory that the theme is in
     */
    public function __construct(\Twig_Environment $twig, Dir $themeDir, $themeManifest)
    {
        $this->twig = $twig;
        $this->themeDir = $themeDir;
        $this->themeManifest = $themeManifest;

        // Set up the markdown parser
        //TODO: Move this to service?
        $this->markdownParser = new MarkdownParser();
    }

    /**
     * Build
     *
     * Recursively build the pages for each directory
     *
     * @param Dir $sourceDir Directory to pull files from
     * @param Dir $targetDir Directory to build the page in
     */
    public function build(Dir $sourceDir, Dir $targetDir)
    {
        //TODO: Add the source and target dirs to constructor and add to object?
        // Start with copying the files from the source to the target
        $this->copyFiles($sourceDir, $targetDir);

        // Copy theme files
        $this->copyThemeFiles($targetDir);

        // Get the list of files to pass to the essay generator
        $files = $targetDir->getFiles();

        // Build the photo essay
        $essayHtml = new Page\Essay($this->twig, $files);

        //TODO: Build the sub folder gallery
        //TODO: Build the final page passing in the previous results
        //TODO: Move onto next folder


        print_r($targetDir->getPath());

        //TODO: Write the final page HTML to index.html
        //file_put_contents($targetDir->getPath() . '/index.html', $baseHtml);

        //TODO: THE HARD PART: After we'de done with this folder, get all child folders and perform the same. This should be recursive.
    }

    /**
     * Copy Files
     *
     * Copy files from one folder to the next
     *
     * @param Dir $sourceDir Directory to pull files from
     * @param Dir $targetDir Directory to build the page in
     */
    protected function copyFiles(Dir $sourceDir, Dir $targetDir)
    {
        // Copy entire source to target with callbacks
        //TODO: Get it to ignore .DS_Store files etc: https://github.com/crysalead/dir/issues/1
        DirHelper::copy($sourceDir->getPath(), $targetDir->getPath(), array(
            'mode' => 0700,
            'childsOnly' => true,
            'recursive' => true,
        ));

        //TODO: Use Scan to get all image files and optimize them

    }

    /**
     * Copy Theme Files
     *
     * Copy the files listed in teh manifest file into the destination directory
     * @param Dir $targetDir Directory to build the page in
     */
    protected function copyThemeFiles(Dir $targetDir)
    {
        // Copy any root theme files to the target folder
        if (!empty($this->themeManifest['root_files']) && is_array($this->themeManifest['root_files'])) {
            foreach ($this->themeManifest['root_files'] as $file) {
                $filePath = Util::appendToPath($this->themeDir->getPath(), $file);
                if (file_exists($filePath)) {
                    $targetFilePath = Util::appendToPath($targetDir->getPath(), $file);
                    copy($filePath, $targetFilePath);
                }
            }
        }
    }
}
