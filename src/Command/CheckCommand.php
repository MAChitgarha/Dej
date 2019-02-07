<?php

namespace Dej\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Dej\Element\DataValidation;

class CheckCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName("check");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Loading configuration file...");

        // Load configuration file and also validator
        try {
            $dataJson = $this->loadJson("data");
        } catch (Throwable $e) {
            $output->error($e);
        }

        $output->writeln([
            "Loaded successfully.",
            "",
        ]);

        // Check for missing fields
        $output->writeln("Checking for missing important fields...");

        $validated = DataValidation::new($dataJson)->classValidation();
        if (empty($validated->getWarnings(true)))
            $output->writeln("All important fields have been set!");
        $validated->output(true);

        // Check for bad field values (e.g. bad MAC address for interface.mac)
        $output->writeln([
            "",
            "Checking for invalid field values...",
        ]);

        $validated = DataValidation::new($dataJson)->typeValidation();
        if (empty($validated->getWarnings(true)))
            $output->writeln("Looks good!");
        $validated->output(true);
    }
}
