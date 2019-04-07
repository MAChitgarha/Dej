<?php
/**
 * Dej command files.
 *
 * @author Mohammad Amin Chitgarha <machitgarha@outlook.com>
 * @see https://github.com/MAChitgarha/Dej
 */

namespace Dej\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Process\Process;
use Dej\Component\ShellOutput;
use MAChitgarha\Component\Pusheh;
use Webmozart\PathUtil\Path;
use Dej\Exception\OutputException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Starts Dej.
 */
class StartCommand extends BaseCommand
{
    /** @var string PHP executable path, located in ./data/php. */
    protected $phpExecutable;

    /**
     * Sets the PHP executable before starting Dej.
     *
     * @param string|null $name The command name, i.e. start.
     */
    public function __construct(string $name = null)
    {
        $this->phpExecutable = PHP_BINARY;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName("start")
            ->setDescription("Starts Dej.")
            ->setHelp($this->getHelpFromFile("start"))
        ;
    }

    /**
     * @throws OutputException If Dej cannot be restarted.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        assert($output instanceof ShellOutput);

        $this->forceRootPermissions($output);

        $output->writeln("Starting Dej...");

        // If there are some screens running, prompt user
        if (StatusCommand::getStatus() !== StatusCommand::STATUS_STOPPED) {
            // Prompt user to restart Dej or not
            $helper = $this->getHelper("question");
            $question = new ConfirmationQuestion("Already running. Restart? [N(o)/y(es)] ", false);
            if ($helper->ask($input, $output, $question)) {
                $output->writeln("Restarting...");
                try {
                    $result = $this->getApplication()->find("stop")
                        ->run(new ArrayInput([]), new NullOutput());
                } catch (\Throwable $e) {
                }

                // If something goes wrong with stopping Dej
                if (!isset($result) || $result !== 0) {
                    throw new OutputException("Cannot stop Dej.");
                }
                // User canceled starting Dej
            } else {
                $output->writeln("Aborted.");
                return 0;
            }
        }

        // Load configurations and validate it
        $config = $this->loadJson("config")
            ->checkEverything()
            ->throwFirstError();

        // Perform comparison between files and backup files
        $path = $config->get("save_to.path");
        $backupDir = $config->get("backup.dir");

        $this->compareFiles($path, $backupDir);

        // Load executables
        $screen = $config->get("executables.screen");
        $tcpdump = $config->get("executables.tcpdump");

        // Checks for installed commands
        $neededExecutables = [
            ["screen", $screen],
            ["tcpdump", $tcpdump]
        ];
        foreach ($neededExecutables as $executable) {
            if (empty(`which {$executable[1]}`)) {
                throw new OutputException("You must have {$executable[0]} command installed, "
                    . "i.e. the specified executable file cannot be used ({$executable[1]}). "
                    . "Change it by 'dej config'.");
            }
        }

        // Names of directories and files
        $sourceDir = "src/Process";
        $filenames = [
            "Tcpdump",
            "Reader",
            "Sniffer",
            "Backup",
        ];

        // Get logging configurations
        $isLoggingEnabled = $config->get("logs.screen");
        $logsPath = $config->get("logs.path");
        Pusheh::createDirRecursive($logsPath);

        // Run each file with a logger
        foreach ($filenames as $filename) {
            // The logging part; check if it's enabled or not
            $logFile = Path::join($logsPath, "$filename.log");
            $logPart = $isLoggingEnabled ? "-L -Logfile $logFile" : "";

            // Create the command to be executed in a screen
            $mainCommand = "{$this->phpExecutable} "
                // The PHP file path to be run
                . Path::join($sourceDir, "$filename.php") . " "
                /*
                 * Extra arguments to be used in the files:
                 * 1: Path to config.json configuration file,
                 * 2: Path to users.json configuration file.
                 * 1: Path to stop handler file (for the sniffer).
                 */
                . Path::join($this->configDir, "config.json") . " "
                . Path::join($this->configDir, "users.json") . " "
                . $this->stopHandlerFile;

            // Run the process
            $command = "$screen -S $filename.dej $logPart -d -m $mainCommand";
            Process::fromShellCommandline($command)->run();
        }

        $status = StatusCommand::getStatus();
        if ($status === StatusCommand::STATUS_RUNNING) {
            $output->writeln("Done!");
        } elseif ($status === StatusCommand::STATUS_PARTIAL || $status === StatusCommand::STATUS_STOPPED) {
            $output->writeln("Something went wrong. Try again!");
        } else {
            $output->writeln("Too much instances are running.");
        }
    }

    /**
     * Replaces a broken file with its backup.
     *
     * A broken file is a file that is got empty or is smaller than its backup.
     *
     * @param string $path The path of the main files.
     * @param string $backupDir The path of the backup files.
     * @return void
     */
    private function compareFiles(string $path, string $backupDir): void
    {
        // Remove colons from number
        $getNum = function (string $path) {
            return (int)str_replace(",", "", file_get_contents($path));
        };

        // Get files information
        Pusheh::createDirRecursive($path);
        $files = new \DirectoryIterator($path);
        $backupDir = "$path/$backupDir";

        foreach ($files as $file) {
            $filename = $file->getFilename();
            $filePath = Path::join($path, $filename);
            $backupFilePath = Path::join($backupDir, $filename);

            // Replacing broken file
            if (is_dir($backupDir) && file_exists($backupFilePath) &&
                $getNum($backupFilePath) > $getNum($filePath)) {
                // Remove the broken file
                unlink($filePath);

                // Replace it with the backup file
                copy($backupFilePath, $filePath);
            }
        }
    }
}
