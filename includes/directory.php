<?php

$shell = new Shell();

// Creates a directory, if it doesn't exist
function directory(string $dirName, int $chmod = 0777) {
    global $shell;
    // If directory exists, return false
    if (is_dir($dirName))
        return false;

    // If directory created successfully, return true
    if (@mkdir($dirName, $chmod, true))
        return true;
    
    $sh->error("Cannot create $dirName directory.");
}

// Force to a path to use or not to use a slash at the end of it
function forceEndSlash(string $path, bool $useSlash = true): string {
    // To compare
    $lastChar = substr($path, -1);
    $isLastCharSlash = $lastChar === "/" || $lastChar === "\\";

    // Add a slash to the end, if not exists
    if ($useSlash && !$isLastCharSlash)
        return "$path/";

    // Remove the end slash, if exists
    if (!$useSlash && $isLastCharSlash)
        return substr($path, 0, -1);

    // Return the first value, if no changes were made
    return $path;
}
