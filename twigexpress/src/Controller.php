<?php

namespace Gradientz\TwigExpress;

class Controller
{
    /** @var array - User configuration */
    public $config;

    /** @var array - Valid Twig namespaces (from user configuration) */
    public $namespaces = [];

    /** @var string - Full path of project/server root */
    public $docRoot;

    /** @var string - URL prefix if project root is below an Apache DocumentRoot */
    private $baseUrl;

    /** @var string - Requested path (from root dir) */
    private $requestPath;

    /** @var string - Full path of the file we found to serve this request */
    private $realFilePath;

    /** @var array */
    private $navInfo;

    /** @var array */
    private $layoutAssets;

    /** @var string - Type of rendering, one of 'file', 'twig', 'dir' or '404' */
    private $renderMode;

    /** @var array - Where we should look for a JSON config file */
    private $configFiles = ['twigexpress.json'];

    /** @var array|string - Glob pattern for files to exclude in dir listings */
    private $excludeFiles = ['*.{php,phar}', '.*'];

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
            $this->showPage(500, [
                'metaTitle' => 'JSON: ' . json_last_error_msg(),
                'title' => 'Problem while parsing your JSON config (' . json_last_error_msg() . ')',
                'message' => 'In <code class="error">'.$file.'</code><br>' .
                    'JSON syntax is rather restrictive, so make sure there’s no syntax error.<br> ' .
                    '<a target="_blank" href="http://jsonlint.com/?json=' . rawurlencode($content) .
                    '">Test it online with JSONLint</a>.'
            ], true);
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
                $this->showPage(500, [
                    'metaTitle' => 'Config Error: Bad Twig namespace',
                    'title' => 'Config Error: Bad Twig namespace',
                    'message' => "<code>\"$name\"</code>: <code>\"$path\"</code> is not a directory."
                ], true);
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
            $candidates[] = $basePath . '/index.html';
        }
        else {
            $ext = pathinfo($basePath, PATHINFO_EXTENSION);
            $candidates[] = $basePath;
            if ($ext !== 'twig') $candidates[] = $basePath . '.twig';
            if ($ext !== 'md')   $candidates[] = $basePath . '.md';
        }
        foreach ($candidates as $c) {
            if (is_file($c)) {
                $match = $c;
                break;
            }
        }
        if ($match) {
            $real = $match;
            $rext = pathinfo($real, PATHINFO_EXTENSION);
            $mode = 'file';
            if ($rext === 'twig') $mode = 'twig';
            if ($rext === 'md')   $mode = 'md';
            if (in_array($rext, ['twig', 'md']) && $rext === $ext) {
                $mode = 'source';
            }
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
        } else {
            return $this->twigEnv = new TwigEnv(
                $this,
                [
                    '_get' => $_GET,
                    '_post' => $_POST,
                    '_cookie' => $_COOKIE,
                    '_base' => $this->baseUrl
                ]
            );
        }
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
        if ($this->renderMode === 'source') {
            return $this->showSource($this->realFilePath);
        }
        if ($this->renderMode === 'md') {
            return $this->showMarkdown($this->realFilePath);
        }
        if ($this->renderMode === 'twig') {
            try {
                $templateId = str_replace($this->docRoot.'/', '', $this->realFilePath);
                $result = $this->twig()->renderUserTemplate($templateId);
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
    private function showPage($statusCode=404, $data=[], $print=false)
    {
        Utils::sendHeaders($statusCode, 'text/html');
        $html = $this->twig()->renderTwigExpressPage($data);
        if ($print) {
            echo $html;
            exit;
        } else {
            return $html;
        }
    }

    /**
     * Show a file's content with syntax highlighting
     * @param $path
     * @return string
     */
    private function showSource($path)
    {
        $source = file_get_contents($path);
        $lang = pathinfo($path, PATHINFO_EXTENSION);
        return $this->showPage(200, [
            'code' => Utils::formatCodeBlock($source, $lang !== 'md'),
            'codeLang' => $lang,
            'navBorder' => false
        ]);
    }

    /**
     * Show a rendered Markdown file
     * @param $path
     * @return string
     */
    private function showMarkdown($path)
    {
        $source = file_get_contents($path);
        return $this->showPage(200, [
            'content' => Utils::processMarkdown($source)
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
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $noExt = pathinfo($name, PATHINFO_FILENAME);
                $url = $base . (in_array($ext, ['twig', 'md']) ? $noExt : $name);
                $fileList[] = ['name' => $name, 'url' => $url];
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
            $data['codeContext'] = Utils::getHighlightLanguage($template);
        }

        return $this->showPage(500, $data);
    }

    /**
     * Show a basic HTML page/message.
     * Fallback for when internal page template fails.
     */
    public function showMinimalPage($data=[]) {
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

    /**
     * Figure out a <title> and breadcrumb navigation for a page,
     * based on its URL
     * @return array
     */
    public function getNavInfo()
    {
        if (is_array($this->navInfo)) {
            return $this->navInfo;
        }
        // Return docroot folder name (or parent/folder, if short) as site name
        $folder = basename($this->docRoot);
        $parent = basename(dirname($this->docRoot));
        $siteName = strlen($folder) < 5 ? "$parent/$folder" : $folder;
        // Make breadcrumbs
        $path = rtrim($this->requestPath, '/');
        $_url_ = '/';
        $crumbs = [['url' => $_url_, 'name' => $siteName]];
        $folders = array_filter(explode('/', $path));
        $last = array_pop($folders);
        foreach ($folders as $folder) {
            $_url_ .= $folder . '/';
            $crumbs[] = ['url' => $_url_, 'name' => $folder];
        }
        // Set last known item as active
        $_active_ = count($crumbs) - 1;
        // Add last item (sometimes as two separate items for filename/extension)
        if ($last) {
            $real = pathinfo($this->realFilePath, PATHINFO_BASENAME);
            $path_ext = pathinfo($last, PATHINFO_EXTENSION);
            $real_ext = pathinfo($real, PATHINFO_EXTENSION);
            // Static files, or 404
            if (!$real || !in_array($real_ext, ['twig', 'md'])) {
                $_active_ += 1;
                $crumbs[] = ['url' => $_url_.$last, 'name' => $last];
            }
            else {
                $real_noext = pathinfo($real, PATHINFO_FILENAME);
                $crumbs[] = ['url' => $_url_.$real_noext, 'name' => $real_noext];
                $crumbs[] = ['url' => $_url_.$real, 'name' => '.'.$real_ext];
                $_active_ += $real_ext === $path_ext ? 2 : 1;
            }
        }
        // Add 'active' attribute to items
        for ($i=0, $max=count($crumbs); $i < $max; $i++) {
            $crumbs[$i]['active'] = $i === $_active_;
        }
        return $this->navInfo = [
            'title' => $siteName,
            'crumbs' => $crumbs
        ];
    }

    /**
     * Load as strings all the assets we want to inline in TE's main template
     * (Using `source()` in Twig is not an option inside archives)
     * @return array
     */
    public function getLayoutAssets() {
        if (is_array($this->layoutAssets)) {
            return $this->layoutAssets;
        }
        $assets = [
            'css' => 'css/styles.css',
            'svg' => 'svg/sprite.svg',
            'highlightjs' => 'js/highlight.min.js'
        ];
        $content = [];
        $root = dirname(__DIR__) . '/tpl/';
        foreach ($assets as $name => $path) {
            $content[$name] = file_get_contents($root . $path);
        }
        return $this->layoutAssets = $content;
    }
}
