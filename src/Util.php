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
        if (self::isRelativePath($path)) {
            // Make the string absolute
            $path = getcwd() . "/" . $path;
        }

        return $path;
    }

}
