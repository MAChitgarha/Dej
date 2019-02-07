<?php

namespace Dej\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;


class UninstallCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName("uninstall");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkRootPermissions();

        try {
            $output->echo("Preparing to uninstall Dej...");

            // Find where Dej has been installed
            $installationPath = trim(`which dej`);
            if (empty($installationPath))
                $output->error("Not installed yet.");

            // Get agreement
            $helper = $this->getHelper("question");
            $question = new ConfirmationQuestion("Are you sure? [N(o)/y(es)] ", false);
            if (!$helper->ask($input, $output, $question)) {
                $output->exit("Aborted.");
            }

            $output->echo("Uninstalling...");

            // Grant right permissions to be able to remove it
            chmod($installationPath, 0755);

            // Remove the file
            unlink($installationPath);

            $output->echo("Uninstalled successfully.");
        } catch (Throwable $e) {
            $output->error($e);
        }
    }
}