<?php

namespace Fiesta;

/**
 * Class Page
 *
 * Page generator is responsible for creating the overall page which will
 * include the photo essay and and sub folder gallery.
 *
 * @author Grant Lucas
 */
class Page
{
    /**
     * Constructor
     *
     * @param string $essayHtml The final HTML generated for the photo essay
     * component of the page
     * @param string $subGalleryHtml The final HTML generated for the sub
     * folder gallery component of the page
     *
     * @return string Completed HTML for the final index.html page
     */
    public function __construct($essayHtml, $subGalleryHtml)
    {
    }
}
