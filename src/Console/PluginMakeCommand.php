<?php

namespace SamMcdonald\L5Modular\Console;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use SamMcdonald\L5Modular\Console\BaseMakeCommand;

class PluginMakeCommand extends BaseMakeCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'modular:create:plugin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new plugin (folder structure)';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Plugin';


    /**
     * Execute the console command 
     *
     * @return void
     */
    public function handle()
    {
        $this->info('Preparing to make a new Plugin..');
        
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
            ['name', InputArgument::REQUIRED, 'Plugin name.'],
        ];
    }

}
