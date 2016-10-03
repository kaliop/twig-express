<?php

namespace Gradientz\TwigExpress;

use joshtronic\LoremIpsum;
use Parsedown;
use Twig_Environment;
use Twig_Error;
use Twig_Extension_Debug;
use Twig_Loader_Filesystem;
use Twig_SimpleFunction;

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

    /** @var string */
    private $docRoot;

    /** @var Twig_Environment */
    private $env;

    /** @var Parsedown */
    private $markdown;

    /** @var LoremIpsum */
    private $lorem;

    /**
     * TwigEnv constructor
     * @param string $root Document root
     * @param array $userConfig
     * @param array $globals
     */
    public function __construct($root, $userConfig=[], $globals=[])
    {
        // Save document root as we need to use it often in methods
        $this->docRoot = $root;

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
        $this->env = $this->makeTwigEnv($config, $globals);
    }

    /**
     * Prepare the Twig environment
     * @param array $config Twig_Environment config
     * @param array $globals Global variables
     * @return Twig_Environment
     */
    private function makeTwigEnv($config, $globals)
    {
        $loader = new Twig_Loader_Filesystem($this->docRoot);
        $env = new Twig_Environment($loader, $config);

        // Enable the 'dump' function
        $env->addExtension(new Twig_Extension_Debug());

        // Add the global variables from the config
        foreach ($globals as $key => $value){
            $env->addGlobal($key, $value);
        }

        // Twig function for generating fake latin text
        // Usage:
        //   {{ lorem('5 words') }}
        //   {% for paragraph in lorem('[10p]') %}
        //     <p>{{ paragraph }}</p>
        //   {% endfor %}
        $env->addFunction(new Twig_SimpleFunction('lorem', [$this, 'makeLoremIpsum']));

        // Twig filter that transforms a string with Parsedown
        // Usage:
        //   {{ markdown(someText) }}
        //   {{ markdown(someText, inline=true) }}
        $env->addFunction(new Twig_SimpleFunction('markdown', [$this, 'processMarkdown']));

        // Twig function that lists files for one or several glob patterns
        // Usage:
        //   {% set files = files('*.*') %}
        //   {% set files = files('*.*', 'starting-folder') %}
        $env->addFunction(new Twig_SimpleFunction('files', [$this, 'listFiles']));

        // Twig function that lists folders for one or several glob patterns
        // Usage:
        //   {% set folders = folders('*') %}
        //   {% set folders = folders('*', 'starting-folder') %}
        $env->addFunction(new Twig_SimpleFunction('folders', [$this, 'listFolders']));

        return $env;
    }

    /**
     * Render a Twig template
     * @param string $template
     * @param array $data
     * @return string
     */
    public function render($template, $data=[])
    {
        return $this->env->render($template, $data);
    }

    /**
     * Render our internal Twig template
     * We use a different loading strategy for compatibility with PHAR archives
     * @param array $data
     * @return string
     */
    public function renderPage($data=[])
    {
        $root = dirname(__DIR__) . '/tpl/';
        $source = file_get_contents($root . 'page.twig');
        $template = $this->env->createTemplate($source);
        // Load all the assets we want to inline
        // (Using `source()` in Twig is not an option inside archives)
        $data['assets'] = [
            'css' => file_get_contents($root . 'css/styles.css'),
            'sprite' => file_get_contents($root . 'svg/sprite.svg'),
            'highlightjs' => file_get_contents($root . 'js/highlight.min.js')
        ];
        try {
            return $template->render($data);
        }
        // Woops, Twig rendering failed, fallback to minimal HTML
        catch (\Twig_Error $error) {
            $html = '';
            foreach(['title'=>'h1', 'subtitle'=>'h2', 'message'=>'blockquote'] as $key=>$tag) {
                if (array_key_exists($key, $data)) {
                    $value = $data[$key];
                    $html .= "<$tag>$value</$tag>\n";
                }
            }
            return $html . "\n" . (string) $error;
        }
    }

    /**
     * Generate fake latin text using joshtronic\LoremIpsum
     *
     * Syntax for command string is:
     *     'min-max type'   -> returns a string
     *     '[min-max type]' -> returns an array
     *
     * Available types:
     * - 'words' (synonyms: 'word', 'w')
     * - 'sentences' (synonyms: 'sentence', 's')
     * - 'paragraphs' (synonyms: 'paragraph', 'p')
     *
     * @param string $command Count and type of content to generate
     * @return array|string
     */
    public function makeLoremIpsum($command='1-7w')
    {
        if (!is_string($command)) return '';
        if (!preg_match('/^\[?\s*(\d{1,3})(-\d{1,3})?\s*([a-z]{1,10})\s*\]?$/', strtolower(trim($command)), $matches)) {
            return '';
        }
        if ($matches[2]) {
            $min = (int) $matches[1];
            $max = (int) substr($matches[2], 1);
            $count = $min <= $max ? rand($min, $max) : rand($max, $min);
        } else {
            $count = (int) $matches[1];
        }
        $method = 'words';
        switch ($matches[3]) {
            case 'w': case 'word': case 'words':
            $method = 'words'; break;
            case 's': case 'sentence': case 'sentences':
            $method = 'sentences'; break;
            case 'p': case 'paragraph': case 'paragraphs':
            $method = 'paragraphs'; break;
        }
        $method .= strpos($matches[0], '[') === 0 ? 'Array' : '';

        if ($this->lorem === null) {
            // Prepare the generator, calling it once to call the private shuffle
            // method (and avoid getting 'lorem ipsum' every time).
            $this->lorem = new LoremIpsum();
            $this->lorem->word();
        }

        if (method_exists($this->lorem, $method)) {
            $args = array_merge([$count], array_slice(func_get_args(), 1));
            $result = call_user_func_array( [$this->lorem, $method], $args );
            // Make sure we use a capital letter first, because LoremIpsum
            // doesn't do it for words. So we're more consistent, and users
            // who don't want that can still use the |lower Twig filter.
            if (is_string($result)) {
                return ucfirst($result);
            }
            if (is_array($result)) {
                return array_map('ucfirst', $result);
            }
        }
        return '';
    }

    /**
     * Transform a string with Parsedown
     * @param string  $text   Markdown text to process
     * @param boolean $inline Do not output paragraph-level tags
     * @return string
     */
    public function processMarkdown($text, $inline=false)
    {
        // We might end up with Twig objects in some cases
        $value = (string) $text;
        if ($this->markdown === null) {
            $this->markdown = Parsedown::instance();
        }
        if ($inline) return $this->markdown->line($value);
        else return $this->markdown->text($value);
    }

    /**
     * Use the glob method to return a list of file names
     * @param string $patterns
     * @param string $where
     * @return array
     */
    public function listFiles($patterns='*', $where='')
    {
        $root = $this->docRoot;
        if (is_string($where)) $root .= '/' . trim($where, '/');
        return Utils::glob($patterns, $root, 'file');
    }

    /**
     * Use the glob method to return a list of folder names
     * @param string $patterns
     * @param string $where
     * @return array
     */
    public function listFolders($patterns='*', $where='')
    {
        $root = $this->docRoot;
        if (is_string($where)) $root .= '/' . trim($where, '/');
        return Utils::glob($patterns, $root, 'dir');
    }
}
