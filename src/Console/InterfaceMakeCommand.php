<?php

namespace SamMcdonald\L5Modular\Console;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Console\GeneratorCommand;

class InterfaceMakeCommand extends GeneratorCommand
{

    /**
     * Laravel version
     *
     * @var string
     */
    protected $version;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'modular:create:interface';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create interface files for Modular-Pattern';


    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Interface';


    /**
     * The current stub.
     *
     * @var string
     */
    protected $currentStub;

    /**
     * The prepared filename for stub
     * 
     * @var [type]
     */
    protected $prep_filename;

    /**
     * [The prepared folder for the stub
     * 
     * @var [type]
     */
    protected $prep_folder;



    protected $addonPath;


    protected $errors;

   
    protected function exitAndShowErrors() {

        $error_string = '';

        foreach($this->errors as $err) {
            $error_string .= $err."\r\n";
        } 

        return $this->error($error_string);
    }

    protected function preChecks() {
        $app = app();

        $this->version = (int) str_replace('.', '', $app->version());

        // Ensure we have Laravel 5.3
        if ($this->version < 530) {
            $this->errors[] = $this->error($this->type.' only compatible with Laravel 5.3');
            return false;
        }

        return true;
    }


    public function handle()
    {

        $this->errors = [];
        /**
         * No point continuing if this fails
         */
        if(!$this->preChecks()) {
            //Build error display
            return $this->exitAndShowErrors();
        }

        // Paths to files
        $imod = app_path().'/Contracts/IModule.php';
        $inst = app_path().'/Contracts/Installable.php';


        $check_imodule = false;
        $check_installable = false;


        if ($this->option('all')) 
        { 
            $check_imodule = true;
            $check_installable = true;
        }

        if ($this->option('imodule')) 
        { 
            $check_imodule = true;  
        }

        if ($this->option('installable')) 
        { 
            $check_installable = true;  
        }

        /**
         * Checks and exit
         */
        if ($check_imodule) {
            if($this->checkAndLogPath($imod, 'IModule')) {
                $this->generate('imodule');
            }
        }

        if ($check_installable) {
            if($this->checkAndLogPath($inst, 'Installable')) {
                $this->generate('installable');
            }
        }


        $this->exitAndShowErrors();      

        $this->info('Program completed...');
    }


    private function checkAndLogPath($path, $type = 'Installable') 
    {
        if ($this->files->exists($path) )
        {
            $this->errors[] = $type.' already exists!';
            return false;
        }

        return true;
    }



    /**
     * Generate file/Class from stub
     * 
     * @param  [type] $type [description]
     * @return [type]       [description]
     */
    protected function generate($type) 
    {   

        $this->preparePath($type);

        $name = $this->qualifyClass('/'.$this->getPreppedPath() );

        $path = $this->getPath($name);
 
        if ($this->files->exists($path)) 
        {
            $this->errors[] = $this->type.' already exists!';
            return $this->error('FatalError: Exiting program');
        }

        // Path to the stubs
        $this->currentStub = __DIR__.'/stubs/'.$type.'.stub';


        $this->makeDirectory($path);

        $this->files->put($path, $this->buildClass($name));
    }

    protected function getPreppedPath() {
        return $this->prep_folder.$this->prep_filename;
    }

    protected function preparePath(string $type = 'imodule') {
 
        $folder = 'Contracts\\';

        switch ($type) 
        {          
            case 'imodule':
                $filename = 'IModule';
                break;

            case 'installable':
                $filename = 'Installable';
                break;
        } 

        $this->prep_filename = $filename;
        $this->prep_folder = $folder;
    }

    /**
     * Get the full namespace name for a given class.
     *
     * @param  string  $name
     * @return string
     */
    protected function getNamespace($name)
    {
        $name = str_replace('\\routes\\', '\\', $name);
        return trim(implode('\\', array_map('ucfirst', array_slice(explode('\\', studly_case($name)), 0, -1))), '\\');
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());
        return $this->replaceName($stub, $this->getNameInput())->replaceNamespace($stub, $name)->replaceClass($stub, $name);
    }

    /**
     * Replace the name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceName(&$stub, $name) {
        $stub = str_replace('DummyTitle', $name, $stub);
        $stub = str_replace('DummyUCtitle', ucfirst(studly_case($name)), $stub);
        $stub = str_replace('DummyLCtitle', strtolower(studly_case($name)), $stub);
        return $this;
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $class = class_basename($name);
        return str_replace('DummyClass', $class, $stub);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->currentStub;
    }


    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return 
        [
            ['imodule', null, InputOption::VALUE_NONE, 'Only create the IModule interface'],
            ['installable', null, InputOption::VALUE_NONE, 'Only create the Installable interface'],
            ['all', null, InputOption::VALUE_NONE, 'Create Both files'],
        ];
    }


    protected function getArguments()
    {
        return 
        [
            ['name', InputArgument::OPTIONAL, 'Interface name.'],
        ];
    }    
}
