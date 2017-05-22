<?php

namespace Gradientz\TwigExpress;

class Controller
{
    /** @var string - Full path of project/server root */
    public $docRoot;
    /** @var string - Requested path (from root dir) */
    public $requestPath;
    /** @var string - URL prefix if project root is below an Apache DocumentRoot */
    public $baseUrl;
    /** @var array - Breadcrumbs for the current request */
    public $crumbs;

    /** @var string - Full path of the file we found to serve this request */
    public $realFilePath;
    /** @var string - Type of rendering, one of 'file', 'twig', 'dir' or '404' */
    public $renderMode;

    /** @var array - User configuration */
    private $config;
    /** @var array - Valid Twig namespaces (from user configuration) */
    private $namespaces = [];
    /** @var array - Where we should look for a JSON config file */
    private $configFiles = ['twigexpress.json'];
    /** @var array|string - Glob pattern for files to exclude in dir listings */
    private $excludeFiles = ['twigexpress.json', '*.{php,phar}', '.*'];
    /** @var array|string - Glob pattern for folders to exclude in dir listings */
    private $excludeDirs = '.*';

    /** @var null|TwigEnv - Our custom twig environment wrapper */
    private $twigEnv = null;

    /**
     * Resolve the document root, request path and base URL
     */
    public function __construct()
    {
        // Figure out the root dir
        $docRoot = Utils::getCleanPath($_SERVER['DOCUMENT_ROOT']);
        $scriptName = Utils::getCleanPath($_SERVER['SCRIPT_FILENAME']);
        $scriptRoot = preg_replace('/\/twigexpress(\.phar|\/start\.php)$/', '', $scriptName);
        $requestPath = explode('?', Utils::getCleanPath($_SERVER['REQUEST_URI']))[0];

        // Simpler case: we trust the document root we have
        $this->docRoot = $docRoot;
        $this->requestPath = $requestPath;
        $this->baseUrl = '/';

        // If we have a conventional folder structure for Apache, with a .htaccess
        // and a twigexpress.phar at the root of the folder, try to guess if we’re
        // in a subfolder, and correct the root dir, request path and base URL.
        if (php_sapi_name() !== 'cli-server' && $scriptRoot !== $docRoot && file_exists($scriptRoot . '/.htaccess')) {
            $trimmed = $requestPath;
            $baseUrl = '/';
            $parts = array_filter(explode('/', $scriptRoot));
            // For a script root of '/a/b/c', try to remove '/c' at the start of the
            // request URI, then '/b/c', then '/a/b/c'. Latest match wins.
            for ($i=count($parts); $i > 0; $i--) {
                $pattern = '/' . implode('/', array_slice($parts, $i-1));
                $result = preg_replace('#^'. preg_quote($pattern) .'#', '', $requestPath);
                if ($result !== $requestPath) {
                    $trimmed = $result;
                    $baseUrl = $pattern . '/';
                }
            }
            $this->docRoot = $scriptRoot;
            $this->requestPath = $trimmed;
            $this->baseUrl = $baseUrl;
        }

        // Prepare user config
        $this->config = $this->getUserConfig();
        if (array_key_exists('namespaces', $this->config)) {
            $this->namespaces = $this->checkNamespaces($this->config['namespaces']);
        }

        // Can we find the requested file?
        $finfo = $this->findRequestedFile($this->requestPath);
        $this->realFilePath = $finfo['path'];
        $this->renderMode = $finfo['mode'];
    }

    /**
     * Retrieve JSON user config
     * @return array
     */
    private function getUserConfig()
    {
        $file = null;
        foreach ($this->configFiles as $f) {
            $fpath = $this->docRoot . '/' . $f;
            if (substr($fpath, -5) === '.json' && file_exists($fpath)) {
                $file = $fpath; break;
            }
        }
        if ($file === null) return [];
        $content = file_get_contents($file);
        $config = json_decode($content, true);
        if ($jsonError = json_last_error()) {
            echo $this->showPage(500, [
                'metaTitle' => 'JSON: ' . json_last_error_msg(),
                'title' => 'Problem while parsing your JSON config (' . json_last_error_msg() . ')',
                'message' => 'In <code class="error">'.$file.'</code><br>' .
                    'JSON syntax is rather restrictive, so make sure there’s no syntax error.<br> ' .
                    '<a target="_blank" href="http://jsonlint.com/?json=' . rawurlencode($content) .
                    '">Test it online with JSONLint</a>.'
            ]);
            exit;
        }
        return $config;
    }

    /**
     * Check that user-configured Twig namespaces are real directories
     * @param array $namespaceConf
     * @return array
     */
    private function checkNamespaces($namespaceConf)
    {
        // Validate that Twig namespaces exist
        $valid = [];

        foreach($namespaceConf as $name=>$path) {
            if (!is_string($name) || !is_string($path)) continue;
            // Treat paths are absolute unless starting with './'
            if (strpos($path, './') === 0) {
                $path = $this->docRoot . '/' . substr($path, 2);
            }
            if (!is_dir($path)) {
                echo $this->showPage(500, [
                    'metaTitle' => 'Config Error: Bad Twig namespace',
                    'title' => 'Config Error: Bad Twig namespace',
                    'message' => "<code>\"$name\"</code>: <code>\"$path\"</code> is not a directory."
                ]);
                exit;
            }
            $valid[$name] = $path;
        }

        return $valid;
    }

    /**
     * Check that files exist either directly as-is or as a Twig template
     * @param string $relativePath Requested path
     * @return array
     */
    private function findRequestedFile($relativePath)
    {
        $basePath = rtrim($this->docRoot, '/').'/'.trim($relativePath, '/');
        $real = null;
        $mode = '404';

        // Allows loading index.html or index.twig
        $candidates = [];
        $match = null;
        $isDir = is_dir($basePath);
        if ($isDir) {
            // Might be overwritten if we find one of the candidates
            $real = $basePath;
            $mode = 'dir';
            $candidates[] = $basePath . '/index.twig';
            $candidates[] = $basePath . '/index.html.twig';
            $candidates[] = $basePath . '/index.html';
        }
        else {
            $candidates[] = $basePath;
            if (substr($basePath, -5) !== '.twig') {
                $candidates[] = $basePath . '.twig';
            }
        }
        foreach ($candidates as $c) {
            if (is_file($c)) {
                $match = $c;
                break;
            }
        }
        if ($match) {
            $real = $match;
            $mode = substr($match, -5) === '.twig' ? 'twig' : 'file';
        }
        return [
            'path' => $real,
            'mode' => $mode
        ];
    }

    /**
     * Set up and cache the Twig environment so we only make it once
     * @returns TwigEnv
     */
    private function twig()
    {
        if ($this->twigEnv !== null) {
            return $this->twigEnv;
        }
        // Load all the things we need for the twig environment
        require_once __DIR__ . '/TwigEnv.php';
        require_once __DIR__ . '/../lib/LoremIpsum/LoremIpsum.php';
        require_once __DIR__ . '/../lib/Parsedown/Parsedown.php';
        require_once __DIR__ . '/../lib/Twig/Autoloader.php';
        \Twig_Autoloader::register();

        return $this->twigEnv = new TwigEnv(
            $this->docRoot,
            $this->config,
            $this->namespaces,
            [
                '_get' => $_GET,
                '_post' => $_POST,
                '_cookie' => $_COOKIE,
                '_base' => $this->baseUrl
            ]
        );
    }

    /**
     * Serve or render the requested file
     * Will send HTTP headers and echo a string or use readfile.
     * @return mixed
     */
    public function output()
    {
        if ($this->renderMode === 'file') {
            Utils::sendHeaders('200', 'application/octet-stream', $this->realFilePath);
            return readfile($this->realFilePath);
        }
        if ($this->renderMode === 'twig') {
            // Was this a request for a twig source to begin with?
            if (substr($this->requestPath, -5) === '.twig') {
                return $this->showSource($this->realFilePath);
            }
            try {
                $templateId = str_replace($this->docRoot.'/', '', $this->realFilePath);
                $result = $this->twig()->render($templateId);
                Utils::sendHeaders('200', 'text/html', $this->realFilePath);
                return $result;
            }
            catch (\Twig_Error $error) {
                return $this->showTwigError($error);
            }
        }
        if ($this->renderMode === '404') {
            return $this->show404();
        }
        if ($this->renderMode === 'dir') {
            return $this->showDir();
        }
        return '';
    }

    /**
     * Render an info or error page
     * @param int $statusCode HTTP status code
     * @param array $data Variables for the error template
     * @return string
     */
    private function showPage($statusCode=404, $data=[])
    {
        Utils::sendHeaders($statusCode, 'text/html');

        // Prepare the <title>
        $base  = rtrim($this->baseUrl, '/');
        $host  = $_SERVER['HTTP_HOST'] . ($base ? $base : '');
        $title = $host;
        $url   = trim($this->requestPath, '/');
        $path  = $this->realFilePath;
        if ($path) {
            $path = trim(str_replace($this->docRoot, '', $path), '/');
        }
        if ($url) {
            $parts = explode('/', $url);
            $title = array_pop($parts) . ' - ' . $title;
        }
        if (in_array((string) $statusCode, ['403', '404', '500'])) {
            $title = 'Error: ' . $title;
        }

        // Prepare breadcrumbs
        $crumbPath = $path ? $path : $url;
        $separateTwigExt = $statusCode !== 404;
        $crumbs = Utils::makeBreadcrumbs($base, $host, $crumbPath, $separateTwigExt);

        // Rewind breadcrumbs by one step if the current URL is for a Twig
        // template but does not have the '.twig' extension.
        $active = count($crumbs);
        if ($path && substr($path, -5) === '.twig' && substr($url, -5) !== '.twig') {
            $active--;
        }

        // Prepare page data
        $data = array_merge([
            'metaTitle' => $title,
            'crumbs' => $crumbs,
            'activeCrumb' => $active
        ], $data);

        return $this->twig()->renderPage($data);
    }

    /**
     * Show a Twig file with syntax highlighting
     * @param $path
     * @return string
     */
    private function showSource($path)
    {
        $source = file_get_contents($path);
        return $this->showPage(200, [
            'code' => Utils::formatCodeBlock($source, true),
            'navBorder' => false
        ]);
    }

    /**
     * Prepare a 404 page
     * @return string
     */
    private function show404()
    {
        $root = rtrim($this->docRoot, '/');
        $path = rtrim($this->requestPath, '/');
        if ($path && substr($path, -5) !== '.twig') $path .= '[.twig]';
        $message = "Could not find: <code class=\"error\">$path</code><br>\n";
        $message .= "Document root: <code>$root</code>";

        return $this->showPage(404, [
            'title' => 'File does not exist',
            'message' => $message
        ]);
    }

    /**
     * Show a Twig file with syntax highlighting
     * @return string
     */
    private function showDir()
    {
        $root = $this->realFilePath;
        // Collapse multiple slashes (we could end up with '///', collapsed to '/')
        $base = Utils::getCleanPath($this->baseUrl . '/' . $this->requestPath . '/');
        $nope = array_merge(
            Utils::glob($this->excludeFiles, $root, 'file'),
            Utils::glob($this->excludeDirs, $root, 'dir')
        );
        $fileList = [];
        $dirList = [];

        foreach(Utils::glob('*', $root, 'file') as $name) {
            if (!in_array($name, $nope)) {
                if (substr($name, -5) === '.twig') $name = substr($name, 0, -5);
                $fileList[] = ['name' => $name, 'url' => $base.$name];
            }
        }
        foreach(Utils::glob('*', $root, 'dir') as $name) {
            if (!in_array($name, $nope)) {
                $dirList[] = ['name' => $name, 'url' => $base.$name];
            }
        }

        $message = '';
        if (count($dirList) + count($fileList) === 0) {
            $message = 'Empty directory';
        }
        return $this->showPage(200, [
            'fileList' => $fileList,
            'dirList' => $dirList,
            'message' => $message,
            'navBorder' => $message !== ''
        ]);
    }

    /**
     * Render a Twig error in a custom page with a code extract
     * @param \Twig_Error $error
     * @return string
     */
    private function showTwigError(\Twig_Error $error)
    {
        $message = $error->getRawMessage();
        $line = $error->getTemplateLine();
        // The template where the error happens might be different
        // than then main template we’re rendering (e.g. with includes)
        $template = $error->getTemplateFile();

        $data = [
            'metaTitle' => 'Error: ' . basename($template),
            'title' => get_class($error),
            'message' => "$message<br>\nLine $line of <code>$template</code>"
        ];

        // Get a few lines of code from the buggy template
        if (file_exists($file = $this->docRoot.'/'.$template)) {
            $code = file_get_contents($file);
            $data['code'] = Utils::formatCodeBlock($code, true, $line, 5);
            $data['codeLang'] = Utils::getHighlightLanguage($template);
        }

        return $this->showPage(500, $data);
    }
}
