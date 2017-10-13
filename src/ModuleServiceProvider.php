<?php

namespace SamMcdonald\L5Modular;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Request;

class ModuleServiceProvider extends ServiceProvider {

    /**
     * Laravel Filesystem object
     * 
     * @var Illuminate\Filesystem\Filesystem
     */
    protected $files;


    /**
     * Theme slug|name
     * 
     * Default is `default` or override in config/l5modular.php file
     * 
     *     'l5modular.theme' => 'theme_name'
     *
     *      Path:
     *      app/Themes/{theme-name}/...  
     *                    
     * @var String
     * 
     */
    protected $theme;


    /**
     * A string representing a subdirectory of the 
     * theme. Will either be admin or public
     * based on the URL segments.
     * 
     * http:abc.com/{admin}/            == 'admin'
     * http:abc.com/{anythingelse}/     == 'public'
     * 
     * @var String
     */
    protected $section;


    /**
     * Generator commands for the Artisan interface
     * 
     * @var Array
     */
    protected $commands = [
        'SamMcdonald\L5Modular\Console\ModuleMakeCommand',
        'SamMcdonald\L5Modular\Console\PluginMakeCommand',
        'SamMcdonald\L5Modular\Console\InterfaceMakeCommand'
    ];

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot() {

        /*
         * Set the name of the Theme.
         */
        $this->theme = config('l5modular.theme') ?: 'default';

        /*
         * Section will be either 'admin' or 'public'
         */
        $this->section = (strtolower(Request::segment(1)) == "admin") ? 'admin' : 'public';

        /* Overloaded Views
         * 
         * Adds theme path for overloaded views.
         */
        View::addLocation(app_path()."/Themes/{$this->theme}/{$this->section}");

        /* Shared Views
         *
         * Adds shared views path (we don't add 'shared' so that
         * shared views are clearly known when executing.
         *
         *      return view('shared.index');
         *         
         */
        View::addLocation(app_path()."/Themes/{$this->theme}");

        /*
         * Register Modules
         */
        $this->findModules('Module');

        /*
         * Register Plugins
         */
        $this->findModules('Plugin');

    }

    /**
     * Find all the Modules or Plugins and call the registration
     * 
     * @param  string $type [description]
     * @return [type]       [description]
     */
    private function findModules(string $type = 'Module') {

        $subdir = $type.'s';
        $config = strtolower($type).'enable';

        if (is_dir($appPath = app_path()."/{$subdir}/")) {

            // plugins.enable|modules.enable
            $mods = config($config) ?: array_map('class_basename', $this->files->directories($appPath));
        
            foreach ($mods as $module) {
                $this->registerModule($module, $subdir);
            }
        }
    }

    /**
     * Registers the Module or Plugin
     * 
     * @param  string $module [description]
     * @param  string $subdir [description]
     */
    private function registerModule(string $module, string $subdir = 'Modules') {

        $module_path = app_path() . "/{$subdir}/" . ucfirst($module);

        if (!$this->app->routesAreCached()) {

            $route_files = [
                $module_path . '/Routes/web.php',
                $module_path . '/Routes/api.php',
            ];

            foreach ($route_files as $route_file) {
                $this->includeIfExists( $route_file );
            }
        }

        /*
         * Not all modules or plugins will have a 
         * ServiceProvider but if one exist 
         * we MUST register it.
         */
        $this->loadProvider($module, ($subdir === 'Modules'));

        /*
         * Loads herlpers and translation file paths
         */
        $helper = $module_path . '/Helpers/Helpers.php';
        $trans  = $module_path . '/Translations';

        $this->includeIfExists( $helper );

        if ($this->files->isDirectory($trans)) {
            $this->loadTranslationsFrom($trans, $module);
        }

        /*
         * Finally, we add the Modules View path.
         */
        $this->loadViewsDir($module, $subdir);
    }

    /**
     * Load Views Path for the Module
     * 
     * The view files for a Module or Plugin will be 
     * available in the priority listed below;
     *
     * 1. app/Theme/
     * 2. app/Theme/modules/ModuleName/
     * 3. app/Modules|Plugins/ModuleName/Views/
     * 
     * As laravel takes priority based on which view is registered 
     * first. Priority will always be from the theme path, then 
     * the overloaded path, and finally within the module 
     * Views dir itself.
     *
     * The first path above is registered at the begining of the boot() method..
     */
    private function loadViewsDir(string $module, string $subdir='Modules') {

        /*
         * Overloaded Views (`app/Themes/sometheme/public/ModuleName/`)
         *
         * Path: 
         */
        $theme_path = app_path()."/Themes/{$this->theme}/{$this->section}/modules/{$module}";

        if ($this->files->isDirectory($theme_path)) {
            $this->loadViewsFrom($theme_path, $module);
        }

        /*
         * Default Views
         *
         * Path: `app/(Modules|Plugins)/ModuleName/Views`
         */        
        $views = app_path()."/{$subdir}/".$module.'/Views';

        if ($this->files->isDirectory($views)) {
            $this->loadViewsFrom($views, $module);
        }
    }


    /**
     * Include a given file if it exists.
     * 
     * @param  String $file The filename
     * @return void
     */
    private function includeIfExists(string $file) {

        if ($this->files->exists($file)) {
            include_once $file;
        }  
    }


    /**
     * Register the MyModuleServiceProvider
     * 
     * @param  string       $module   [description]
     * @param  bool|boolean $isModule [description]
     * 
     * @return [type]                 [description]
     * 
     */
    private function loadProvider(string $module, bool $isModule = true) {

        $subdir = ($isModule)? 'Modules' : 'Plugins' ;

        $provider  = app_path() . "/{$subdir}/{$module}/Providers/{$module}ServiceProvider.php";

        if ($this->files->exists($provider)) {
            Log::info($this->rootNamespace());
            Log::info($this->rootNamespace()."\\".$subdir."\\" . ucfirst($module) . "\\Providers\\".ucfirst($module)."ServiceProvider");
            $this->app->register("\\".app()->getNamespace()."".$subdir."\\" . ucfirst($module) . "\\Providers\\".ucfirst($module)."ServiceProvider");
        }
    }

    /**
     * Register the application services.
     * 
     * @return void
     */
    public function register() {
        $this->files = new Filesystem;
        $this->commands($this->commands);
    }

}
