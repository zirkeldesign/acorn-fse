<?php

namespace Zirkeldesign\AcornFSE\Console;

use Roots\Acorn\Console\Commands\Command;

class AcornFSECommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fse';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up FSE support for Sage 10';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('Currently, this command does nothing.');
    }
}
