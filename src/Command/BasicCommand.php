<?php

namespace Dej\Command;

use Symfony\Component\Console\Command\Command;
use Dej\Element\Shell;

abstract class BasicCommand extends Command
{
    public function __construct(string $name = null)
    {
        $this->sh = new Shell();
        parent::__construct($name);
    }

    protected function loadConfiguration(string $filename)
    {
        return new JSONFile(__DIR__ . "/../../config/$filename.json");
    }
}
