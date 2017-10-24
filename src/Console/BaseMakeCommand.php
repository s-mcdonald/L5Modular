<?php

namespace SamMcDonald\L5Modular\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

abstract class BaseMakeCommand extends GeneratorCommand
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
    protected $name = 'make:module';

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



    /**
     * Check requirements
     * 
     * @return [type] [description]
     */
    public function preChecks()
    {
        $app = app();

        $this->version = (int) str_replace('.', '', $app->version());

        // Ensure we have Laravel 5.5
        if ($this->version < 550) {
            return $this->error($this->type.' only compatible with Laravel 5.5');
        }

        $this->addonPath = app_path().'/'.$this->type.'s/'.studly_case($this->getNameInput());

        // Check if module exists
        if ($this->files->exists($this->addonPath)) 
        {
            return $this->error($this->type.' already exists!');
        }
    }

    

    /**
     * Execute the console command >= L5.5.
     * replaces fire() Only required for <= 5.4
     *
     * @return void
     */
    public function handle()
    {

        $this->preChecks();

        // Create ModuleClass
        if($this->type == 'Module') {
            $this->generate('module');
        }
        else {
            $this->generate('plugin');
        }

        // Create Controller
        $this->generate('controller');

        // Create WEB Routes file
        $this->generate('web');
        
        // Create API Routes file
        $this->generate('api');
        
        // Create Helper file
        $this->generate('helper');

        // Create View
        if (! $this->option('no-view')) { 
            $this->generate('view');
        }

        // Create Model
        if (! $this->option('no-model')) { 
            $this->generate('model');
        }

        //Flag for no translation
        if (! $this->option('no-translation')) { 
            $this->generate('translation');
        }

        // Create Migration Table
        if (! $this->option('no-migration')) {
            // without hacky studly_case function
            // foo-bar results in foo-bar and not in foo_bar
            $table = str_plural(snake_case(studly_case($this->getNameInput())));
            $this->call('make:migration', ['name' => "create_{$table}_table", '--create' => $table]);
        }


        // Create Advanced/Extra files
        if ($this->option('advanced')) {
            $this->generate('provider');
            $this->generate('subscriber');
            $this->generate('request');
        }

        $this->info($this->type.' created.');
    }

    /**
     * Generate file/Class from stub
     * 
     * @param  [type] $type [description]
     * @return [type]       [description]
     */
    protected function generate($type) {   

        $this->preparePath($type);

        //Module becomes Module[s]
        $name = $this->qualifyClass($this->type.'s\\'.studly_case(ucfirst($this->getNameInput())).'\\'.$this->getPreppedPath());

        $path = $this->getPath($name);

        if ($this->files->exists($path)) 
        {
            return $this->error($this->type.' already exists!');
        }

        // Path to the stubs
        $this->currentStub = __DIR__.'/stubs/'.$type.'.stub';

        $this->makeDirectory($path);

        $this->files->put($path, $this->buildClass($name));
    }

    protected function getPreppedPath() {
        return $this->prep_folder.$this->prep_filename;
    }

    protected function preparePath(string $type = 'module') {
 
        $folder = '';

        switch ($type) 
        {
            case 'module':            
            case 'plugin':
                $filename = studly_case($this->getNameInput()).ucfirst($type);
                //$folder = '';
                break;

            case 'controller':
                $filename = studly_case($this->getNameInput()).ucfirst($type);
                $folder = 'Controllers\\';
                break;

            case 'model':
                $filename = studly_case($this->getNameInput());
                $folder = 'Models\\';
                break;

            case 'view':
                $filename = 'index.blade';
                $folder = 'Views\\';
                break;
                
            case 'translation':
                $filename = 'default';
                $folder = 'Translations\\';
                break;

            case 'web':
                $filename = 'web';
                $folder = 'Routes\\';
                break;

            case 'api':
                $filename = 'api';
                $folder = 'Routes\\';
                break;
                
            case 'helper':
                $filename   = 'Helpers';
                $folder     = 'Helpers\\';
                break;

            case 'provider':
                $filename = studly_case($this->getNameInput()).'ServiceProvider';
                $folder = 'Providers\\';
                break;

            case 'subscriber':
                $filename = studly_case($this->getNameInput()).ucfirst($type);
                $folder = 'Subscribers\\';
                break;

            case 'request':
                $filename = studly_case($this->getNameInput()).ucfirst($type);
                $folder = 'Requests\\';
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
    protected function replaceName(&$stub, $name)
    {
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
            ['advanced', null, InputOption::VALUE_NONE, 'Create additional files including ServiceProvider classes.'],
            ['no-view', null, InputOption::VALUE_NONE, 'Do not create View files.'],
            ['no-model', null, InputOption::VALUE_NONE, 'Do not create a Model file.'],
            ['no-migration', null, InputOption::VALUE_NONE, 'Do not create new Migration files.'],
            ['no-translation', null, InputOption::VALUE_NONE, 'Do not create Translation filesystem.'],
        ];
    }
}
