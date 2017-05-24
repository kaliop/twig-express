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
        $this->tplRoot = __DIR__ . '/tpl/';
        $userConfig = $controller->config;
        $namespaces = $controller->namespaces;

        // Merge configs before calling the actual environment constructor
        $this->defaults['strict_variables'] = $controller->debugMode;
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
            $layoutPath = $this->tplRoot . 'layout.twig';
            if ($tpl = file_get_contents($layoutPath)) {
                $this->baseLayout = $this->env->createTemplate($tpl);
            } else {
                $this->fallbackErrorPage(['error' => "Cannot find template '$layoutPath'"]);
            }
        } catch (Twig_Error $e) {
            $this->fallbackErrorPage(['error' => $e]);
        }
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
                $this->fallbackErrorPage(array_merge($data, ['error' => $error]));
            }
        }
        else {
            $this->fallbackErrorPage($data);
        }
        return $content;
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

        // Expose the Twig Express template
        $env->addFunction(new Twig_SimpleFunction('twigexpress_layout', function() {
            return $this->baseLayout;
        }));

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
        $env->addFunction(new Twig_SimpleFunction('lorem', function($command='1-7w') {
            return Utils::makeLoremIpsum($command);
        }));

        // Twig filter that transforms a string with Parsedown
        // Usage:
        //   {{ markdown(someText) }}
        //   {{ markdown(someText, inline=true) }}
        $env->addFunction(new Twig_SimpleFunction('markdown', function($text='', $inline=false) {
            return Utils::processMarkdown($text, $inline);
        }));

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
     * Show a basic HTML page/message.
     * Fallback for when internal page template fails.
     * @param array $data
     */
    private function fallbackErrorPage($data=[]) {
        Utils::sendHeaders(500, 'text/html');
        $TAG_MAP = [
            'title' => 'h1',
            'message' => 'blockquote',
            'content' => 'div',
            'code' => 'pre',
            'error' => 'pre'
        ];
        $html = '';
        foreach($TAG_MAP as $name => $tag) {
            $content = array_key_exists($name, $data) ? $data[$name] : '';
            if ($content) {
                $html .= $tag === 'pre' ? '<pre style="white-space:pre-wrap">' : "<$tag>";
                $html .= $content . "</$tag>\n";
            }
        }
        echo $html;
        exit;
    }
}
