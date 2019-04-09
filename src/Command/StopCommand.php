<?php
/**
 * Dej command files.
 *
 * @author Mohammad Amin Chitgarha <machitgarha@outlook.com>
 * @see https://github.com/MAChitgarha/Dej
 */

namespace Dej\Command;

use Symfony\Component\Console\Input\InputInterface;
use Dej\Component\ShellOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Dej\Component\PathData;

/**
 * Stops Dej.
 */
class StopCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName("stop")
            ->setDescription("Stops Dej.")
            ->setHelp($this->getHelpFromFile("stop"))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        assert($output instanceof ShellOutput);

        $this->forceRootPermissions($output);

        $output->writeln("Stopping Dej...");

        // Check if there is at least one screen to continue stopping
        if (StatusCommand::getStatus() === StatusCommand::STATUS_STOPPED) {
            $output->writeln("Not running.");
            return 0;
        }
    
        // Stop Tcpdump and the reader instances
        `screen -X -S Tcpdump.dej quit`;
        `screen -X -S Reader.dej quit`;

        // Send stop signal to the sniffer, and wait for the process to end
        if (StatusCommand::isRunning("sniffer")) {
            $stopperFile = PathData::getStopperFilePath();

            touch($stopperFile);
            while (file_exists($stopperFile)) {
                usleep(100 * 1000);
            }
        }

        // Stop the backup process
        `screen -X -S Backup.dej quit`;

        $output->writeln("Done!");
    }
}
