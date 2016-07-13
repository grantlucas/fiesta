<?php

namespace Fiesta;

/**
 * Utility class
 */
class Util
{
    /**
     * Is relative path
     *
     * Determine if a path is relative or absolute
     *
     * @param string $path
     *
     * @return bool
     */
    public static function isRelativePath($path)
    {
        // Check if the first character is a slash
        if (substr($path, 0, 1) != "/") {
            return true;
        }

        return false;
    }

    /**
     * Make Path Absolute
     *
     * @param string $path
     *
     * @return string Full absolute path
     */
    public static function makePathAbsolute($path)
    {
        $returnPath = $path;

        if (self::isRelativePath($path)) {
            // Make the string absolute
            $returnPath = self::appendToPath(getcwd(), $path);
        }

        // Return the path
        return $returnPath;
    }

    /**
     * Append to path
     *
     * Append an item onto a path checking for trailing directory separator
     *
     * @param string $path The path to append to
     * @param string $addition The portion to add to the path
     *
     * @return string
     */
    public static function appendToPath($path, $addition)
    {
        if (substr($path, -1) != '/' && substr($addition, 1) != '/') {
            // Return with the additional separator
            return $path . '/' . $addition;
        }

        // Return just the appended paths
        return $path . $addition;
    }

}
