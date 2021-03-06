<?php
/**
 * Dej command files.
 *
 * @author Mohammad Amin Chitgarha <machitgarha@outlook.com>
 * @see https://github.com/MAChitgarha/Dej
 */

namespace Dej\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Webmozart\PathUtil\Path;
use Dej\Component\ShellOutput;
use Dej\Exception\OutputException;
use Dej\Exception\InternalException;

/**
 * Installs Dej.
 */
class InstallCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName("install")
            ->addOption("force", "f", InputOption::VALUE_NONE)
            ->setDescription("Installs Dej (or updates it).")
        ;
    }

    /**
     * @throws InternalException When installation path cannot be detected.
     * @throws OutputException When try installing in a repository environment.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        assert($output instanceof ShellOutput);

        $this->forceRootPermissions($output);

        // Force user to install using a Phar (and not Dej repository)
        if (empty($currentPharPath = \Phar::running(false))) {
            throw new OutputException("You must install Dej as a Phar file.");
        }

        $output->writeln("Preparing...");

        // Get options
        $forceMode = $input->getOption("force");

        // Extract $PATH paths
        $sysInstallationDirs = explode(":", getenv("PATH"));
        if (empty($sysInstallationDirs)) {
            throw new InternalException("Cannot find an installation path.");
        }

        $installationDir = $sysInstallationDirs[0];
        $defaultInstallationDir = "/usr/local/bin";
        if (in_array($defaultInstallationDir, $sysInstallationDirs)) {
            $installationDir = $defaultInstallationDir;
        }

        $installationPath = Path::join($installationDir, "dej");

        // Prompt user for overwriting installed version when force mode is not enabled
        if (file_exists($installationPath) && !$forceMode) {
            $question = new ConfirmationQuestion(
                "Overwrite the installed version? [N(o)/y(es)] ",
                false
            );
            if (!$this->getHelper("question")->ask($input, $output, $question)) {
                return $output->abort("Aborted.");
            }
        }

        // Installation
        if (@copy($currentPharPath, $installationPath) && @chmod($installationPath, 0755)) {
            $output->writeln([
                "Installed successfully.",
                "Try 'dej help' for more information."
            ]);
        } else {
            $output->writeln("Installation failed.");
        }
    }
}
