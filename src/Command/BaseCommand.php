<?php
/**
 * Dej command files.
 * 
 * @author Mohammad Amin Chitgarha <machitgarha@outlook.com>
 * @see https://github.com/MAChitgarha/Dej
 */

namespace Dej\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Dej\Component\JSONFileValidation;
use Webmozart\PathUtil\Path;
use MAChitgarha\Component\Pusheh;
use Dej\Exception\OutputException;

/**
 * The base class for all Dej commands.
 */
abstract class BaseCommand extends Command
{
    /**
     * Loads a JSON file.
     * 
     * The default path for configuration files is under the user's home directory.
     *
     * @param string $filename The filename to load, with '.json' suffix.
     * @param string $prefix The prefix directory to load from.
     * @return JSONFileValidation
     */
    protected function loadJson(string $filename): JSONFileValidation
    {
        // Create the config directory
        $configDirectory = Path::join(getenv("HOME"), ".config/dej");
        Pusheh::createDirRecursive($configDirectory);

        return new JSONFileValidation(Path::join($configDirectory, "$filename.json"));
    }

    /**
     * Forces user to grant root permissions.
     *
     * @param OutputInterface $output
     * @return void
     * @throws OutputException When root permission has not been granted.
     */
    protected function forceRootPermissions(OutputInterface $output): void
    {
        // Cannot detect
        $cannotDetectMessage = "We cannot detect if root permissions is granted or not. Please " .
            "make sure you've granted, otherwise you may have problems.";
        if (!function_exists("posix_getuid"))
            $output->writeln($cannotDetectMessage);

        if (posix_getuid() !== 0)
            throw new OutputException("Root permission is needed.");
    }

    /**
     * Gets a help from a file.
     * 
     * It tries to get the help file, but if reading it was unsuccessful, it uses the command's description. Also, you can inject values in the help file contents.
     *
     * @param string $filename The filename of the help, without the 
     * @param array $data Data to be injected. Placeholder is in the form of "{{sth}}".
     * @return string
     */
    protected function getHelpFromFile(string $filename, array $data = []): string
    {
        $filePath = __DIR__ . "/../../data/helps/$filename.txt";

        try {
            $file = new \SplFileObject($filePath, "r");
            $contents = $file->fread($file->getSize());
            $file = null;
        } catch (\Throwable $e) {
            return $this->getDescription();
        }

        foreach ($data as $placeholder => $value)
            $contents = str_replace("{{$placeholder}}", $value, $contents);

        return $contents;
    }
}
