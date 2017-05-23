<?php

namespace Gradientz\TwigExpress;

use Twig_Autoloader;
use Twig_Environment;
use Twig_Error;
use Twig_Extension_Debug;
use Twig_Loader_Filesystem;
use Twig_SimpleFunction;
use Twig_Template;

class TwigEnv
{
    /** @var array */
    private $defaults = [
        'debug' => true,
        'cache' => false,
        'autoescape' => true,
        'strict_variables' => true,
        'charset' => 'utf-8'
    ];

    /** @var Controller */
    private $controller;

    /** @var Twig_Environment */
    private $env;

    /** @var string - Path of TwigExpress internal template */
    private $tplRoot;

    /** @var Twig_Template */
    private $baseLayout;

    /**
     * TwigEnv constructor
     * @param Controller $controller
     * @param array $globals
     */
    public function __construct($controller, $globals=[])
    {
        Twig_Autoloader::register();
        $this->controller = $controller;
        $this->tplRoot = dirname(__DIR__) . '/tpl/';
        $userConfig = $controller->config;
        $namespaces = $controller->namespaces;

        // Merge configs before calling the actual environment constructor
        $config = $this->defaults;

        if (is_array($userConfig)) {
            foreach($config as $key=>$val) {
                if (array_key_exists($key, $userConfig)) {
                    $config[$key] = $userConfig[$key];
                }
            }
            if (array_key_exists('globals', $userConfig)
                && is_array($userConfig['globals'])) {
                $globals = array_merge($globals, $userConfig['globals']);
            }
        }

        // Set up the twig env
        $this->env = $this->makeTwigEnv($config, $namespaces, $globals);

        // Preload the base layout template for navigation pages
        try {
            if ($layout = file_get_contents($this->tplRoot . 'layout.twig')) {
                $this->baseLayout = $this->env->createTemplate($layout);
            }
        } catch (Twig_Error $e) {
            $controller->showMinimalPage(['error' => $e]);
        }
    }

    /**
     * Prepare the Twig environment
     * @param array $config Twig_Environment config
     * @param array $namespaces Twig namespaces to set up
     * @param array $globals Global variables
     * @return Twig_Environment
     */
    private function makeTwigEnv($config, $namespaces, $globals)
    {
        $loader = new Twig_Loader_Filesystem($this->controller->docRoot);
        foreach($namespaces as $ns=>$path) {
            $loader->addPath($path, $ns);
        }
        $env = new Twig_Environment($loader, $config);

        // Twig function for getting the text content of TwigExpress' main layout page
        $env->addFunction(new Twig_SimpleFunction('twigexpress_layout_assets', [$this->controller, 'getLayoutAssets']));

        // Likewise for getting the <title> and breadcrumbs
        $env->addFunction(new Twig_SimpleFunction('twigexpress_layout_navinfo', [$this->controller, 'getNavInfo']));

        // Twig function for generating fake latin text
        // Usage:
        //   {{ lorem('5 words') }}
        //   {% for paragraph in lorem('[10p]') %}
        //     <p>{{ paragraph }}</p>
        //   {% endfor %}
        $env->addFunction(new Twig_SimpleFunction('lorem', 'Utils::makeLoremIpsum'));

        // Twig filter that transforms a string with Parsedown
        // Usage:
        //   {{ markdown(someText) }}
        //   {{ markdown(someText, inline=true) }}
        $env->addFunction(new Twig_SimpleFunction('markdown', 'Utils::processMarkdown'));

        // Twig function that lists files for one or several glob patterns
        // Usage:
        //   {% set files = files('*.*') %}
        //   {% set files = files(['*.json', '*.yaml']) %}
        //   {% set files = files('starting/folder/*.*') %}
        $env->addFunction(new Twig_SimpleFunction('files', function($patterns) {
            return Utils::glob($patterns, $this->controller->docRoot, 'file');
        }));

        // Twig function that lists folders for one or several glob patterns
        $env->addFunction(new Twig_SimpleFunction('folders', function($patterns) {
            return Utils::glob($patterns, $this->controller->docRoot, 'dir');
        }));

        // Enable the 'dump' function
        $env->addExtension(new Twig_Extension_Debug());

        // Add the global variables from the config
        foreach ($globals as $key => $value){
            $env->addGlobal($key, $value);
        }

        return $env;
    }

    /**
     * Render a Twig template
     * @param string $template
     * @param array $data
     * @return string
     */
    public function renderUserTemplate($template, $data=[])
    {
        return $this->env->render($template, $data);
    }

    /**
     * Render our internal Twig template
     * @param array $data
     * @return string
     */
    public function renderTwigExpressPage($data=[])
    {
        $content = '';
        if ($this->baseLayout) {
            try {
                $content = $this->baseLayout->render($data);
            }
            catch (Twig_Error $error) {
                $this->controller->showMinimalPage(array_merge($data, ['error' => $error]));
            }
        }
        else {
            $this->controller->showMinimalPage($data);
        }
        return $content;
    }
}
