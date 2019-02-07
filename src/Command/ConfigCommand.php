<?php

namespace Dej\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use MAChitgarha\Component\JSONFile;
use Symfony\Component\Console\Input\ArrayInput;
use Dej\Element\DataValidation;
use MAChitgarha\Component\Pusheh;

class ConfigCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName("config")
            ->addArgument("index", InputArgument::REQUIRED)
            ->addArgument("value", InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $index = $input->getArgument("index");
        $value = $input->getArgument("value");

        $output->writeln("Preparing...");

        // Load configurations
        $loaded = true;
        try {
            $dataJson = new JSONFile("config/data.json");

        // If file doesn't exist, attemp to create it
        } catch (\Throwable $e) {
            // Create the configuration file
            try {
                $this->createConfigFile();
            } catch (\Throwable $e) {
                $output->echo($e->getMessage());
            }

            // Load it
            try {
                $dataJson = new JSONFile("config/data.json");
            } catch (\Throwable $e) {
                $output->error($e);
            }
        }

        // Load all possible options
        try {
            $types = (new JSONFile("data/validation/type.json"))->get("data\.json");
        } catch (Throwable $e) {
            $output->error($e);
        }

        // Extract all possible options
        $possibleOptions = [];
        foreach ((array)$types as $key => $val)
            array_push($possibleOptions, $key);

        $output->echo("Done!", 2);

        // Break if it is an invalid option
        if (!in_array($index, $possibleOptions)) {
            $output->echo("There is no '$index' option exists.");
            $output->exit("Run 'dej config list' for more info.");
        }

        $output->echo("Updating...");

        // Get field's current value
        $currentValue = $dataJson->get($index);

        // Fix values
        $type = $types->$index->type ?? "string";
        switch ($type) {
            case "bool":
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;

            case "int":
                $value = filter_var($value, FILTER_VALIDATE_INT);
                break;

            case "alphanumeric":
                $value = preg_replace("/[^a-z0-9]/i", "", $value);
                break;
            
            case "mac":
                if (!preg_match("/^([\da-f]{2}:){5}([\da-f]{2})$/i", $value))
                    $output->error("Wrong MAC address was given.");
                break;
        }

        // Change field's value
        $dataJson->set($index, $value);

        // Open the file to save
        try {
            $dataJson->save();
        } catch (Throwable $e) {
            $output->error($e);
        }

        $output->echo("Done!");
        if ($currentValue !== null && $currentValue !== $value)
            $output->echo(json_encode($currentValue) . " -> " . json_encode($value));

        // Restart Dej to see the effects and show the result, if root permissions granted
        try {
            $this->checkRootPermissions();
            $output->echo();
            $this->getApplication()->find("restart")->run(new ArrayInput([]), $output);
        } catch (\Throwable $e) {
            $output->echo("You have to restart Dej to see effects.");
        }

        // Check for warnings
        try {
            $warnings = (new DataValidation(new JSONFile("config/data.json")))
                ->classValidation()
                ->typeValidation()
                ->getWarnings();
        } catch (Throwable $e) {
            $output->error($e);
        }

        // If at least a warning found, print it
        $warningsCount = count($warnings);
        if ($warningsCount !== 0) {
            $output->echo("Found $warningsCount warning(s) in the configuration file.", 1, 1);
            $output->echo("Try 'dej config check' for more details.");
        }
    }

    private function createConfigFile()
    {
        $output->echo("Creating...");

        // Create directory if it does not exist
        Pusheh::createDir("config");
        $dataJsonFile = "config/data.json";

        if (file_exists($dataJsonFile))
            throw new \Exception("Configuration file exists.");

        // Create the configuration
        touch($dataJsonFile);

        // Make right permissions
        chmod($dataJsonFile, 0755);

        $output->echo("Done!");
    }
}
