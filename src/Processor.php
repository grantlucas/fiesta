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
    protected $twigBaseFile = 'base.html.twig';

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
        // Start with copying the files from the source to the target
        $this->copyFiles($sourceDir, $targetDir);

        // Perform processing and build index file in the target directory

        // Get list of files to loop through
        $files = $targetDir->getFiles();

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

                // If this file isn't a markdown or image file, ignore it
                if (!in_array($currentFileType, ['image', 'markdown'])) {
                    continue;
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

                // If we have a group break, increment the counter and process group settings
                if ($groupBreak) {
                    $groupCounter++;

                }

                // Add the final image and/or text file to the processed files array
                $processedFiles[$groupCounter]['files'][$file] = $processedFile;

                // Increment the counter one more time incase there was only
                // one file in the group. This prevents the first file of the
                // next group from being lumped into this group
                if ($groupBreak) {
                    $groupCounter++;
                }
            }
        }

        // Loop through the groups and create group settings
        foreach ($processedFiles as $groupId => $group) {
            $groupSettings = [
                'classes' => [
                    'row'
                ],
            ];

            if (count($processedFiles[$groupId]['files']) > 1) {
            // If there are multiple files in a group, set it to grid
                $groupSettings['classes'][] = 'grid';
            } elseif (count($processedFiles[$groupId]['files'] == 1)) {
                // If there was only one file, check if it has a layout, and add that as a class
                $file = current($processedFiles[$groupId]['files']);

                if (isset($file['image']['settings']['image-layout'])) {
                    $groupSettings['classes'][] = $file['image']['settings']['image-layout'];
                }
            }

            // Add the group settings
            $processedFiles[$groupId]['settings'] = $groupSettings;
        }


        print_r($processedFiles);

        // Load the template's base file
        $baseTemplate = $this->twig->loadTemplate($this->twigBaseFile);

        $baseHtml = $baseTemplate->render(array(
            'fileGroups' => $processedFiles,
        ));

        print_r($baseHtml);
        print_r($targetDir->getPath());

        // Create the index.html file with the rendered HTML
        file_put_contents($targetDir->getPath() . '/index.html', $baseHtml);

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
