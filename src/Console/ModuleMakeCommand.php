<?php

namespace SamMcDonald\L5Modular\Console;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use SamMcDonald\L5Modular\Console\BaseMakeCommand;

class ModuleMakeCommand extends BaseMakeCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'modular:create:module';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new module (folder structure)';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Module';


    /**
     * Execute the console command 
     *
     * @return void
     */
    public function handle()
    {
        $this->info('Preparing to make a new Module..');

        if ($this->confirm('Do you wish to continue?')) {
            parent::handle();
        }
    }


    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return 
        [
            ['name', InputArgument::REQUIRED, 'Module name.'],
        ];
    }

}
