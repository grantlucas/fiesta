<?php

namespace Fiesta\Page;

/**
 * Class Essay
 *
 * Generate the HTML for the photo essay component of the page.
 *
 * @author Grant Lucas
 */
class Essay
{
    /**
     * @var Essay base file
     */
    protected $twigEssayTemplateFile = 'essay.html.twig';

    /**
     * @var Array of processed files
     */
    protected $processedFiles = array();

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * Constructor
     *
     * @param \Twig_Environment $twig
     * @param array $files List of files to operate on
     */
    public function __construct(\Twig_Environment $twig, $files)
    {
        $this->twig = $twig;

        // Process the files

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
                $this->processedFiles[$groupCounter]['files'][$file] = $processedFile;

                // Increment the counter one more time incase there was only
                // one file in the group. This prevents the first file of the
                // next group from being lumped into this group
                if ($groupBreak) {
                    $groupCounter++;
                }
            }
        }

        // Loop through the groups and create group settings
        foreach ($this->processedFiles as $groupId => $group) {
            $groupSettings = [
                'classes' => [
                    'row'
                ],
            ];

            if (count($this->processedFiles[$groupId]['files']) > 1) {
                // If there are multiple files in a group, set it to grid
                $groupSettings['classes'][] = 'grid';
            } elseif (count($this->processedFiles[$groupId]['files'] == 1)) {
                // If there was only one file, check if it has a layout, and add that as a class
                $file = current($this->processedFiles[$groupId]['files']);

                if (isset($file['image']['settings']['image-layout'])) {
                    $groupSettings['classes'][] = $file['image']['settings']['image-layout'];
                }
            }

            // Add the group settings
            $this->processedFiles[$groupId]['settings'] = $groupSettings;
        }

        print_r($this->processedFiles);
    }

    /**
     * Get HTML
     *
     * @return string HTML of the photo essay
     */
    public function getHTML()
    {
        // Load the template
        $essayTemplate = $this->twig->loadTemplate($this->twigEssayTemplateFile);

        // Build the HTML for the photo essay
        $essayHtml = $essayTemplate->render([
            'fileGroups' => $this->processedFiles,
        ]);

        print_r($essayHtml);

        return $essayHtml;

    }
}
