<?php

namespace TwigExpress;

class Controller
{
    /** @var array - User configuration */
    public $config;

    /** @var array - Whitelist of allowed filename patterns (user config) */
    public $allowOnly;

    /** @var bool - Allow directory browsing, showing Twig/Markdown sources, etc. */
    public $debugMode = true;

    /** @var array - Valid Twig namespaces (from user configuration) */
    public $namespaces = [];

    /** @var string - Full path of project/server root */
    public $docRoot;

    /** @var string - Requested path (from root dir) */
    private $reqPath;

    /** @var string - Full path of the file we found to serve this request */
    private $realPath;

    /** @var string - Type of rendering, one of 'file', 'twig', 'dir' or '404' */
    private $renderMode;

    /** @var array - Where we should look for a JSON config file */
    private $configFiles = ['twigexpress.json'];

    /**
     * @var array - Blacklist of extensions to avoid serving
     * Not configurable, not a solid security measure, don't put TwigExpress
     * on a live server EVER thank you very much.
     */
    private $blockTypes = [
        'twigexpress.*', '*.php', '*.phar', '.htaccess', '.htpasswd', '*.sql'
    ];

    /** @var array - Filenames for directory indexes (order sets priority) */
    private $indexFiles = ['index.html', 'index.twig'];

    /** @var array - Extensions for file types we can render (order sets priority) */
    private $renderExt = ['twig', 'md', 'markdown'];

    /** @var null|TwigEnv - Our custom twig environment wrapper */
    private $twigEnv;

    /** @var array - cache for navigation/breadcrumbs info */
    private $navInfo;

    /** @var array - cache for the TwigExpress page's assets */
    private $layoutAssets;

    /**
     * Resolve the document root, request path and base URL
     */
    public function __construct()
    {
        // Figure out the root dir
        $docRoot = Utils::getCleanPath($_SERVER['DOCUMENT_ROOT'], 'r');
        $scriptName = Utils::getCleanPath($_SERVER['SCRIPT_FILENAME'], 'r');
        $scriptRoot = dirname(preg_replace('#/start.php$#', '', $scriptName));

        // Simpler case: we trust the document root we have
        $this->docRoot = $docRoot;
        // Apache with a dynamic vhost can have a docroot completely different
        // from where the twigexpress phar/script lives.
        if (php_sapi_name() !== 'cli-server' && $scriptRoot !== $docRoot) {
            // treat as document root if we have a config file or htaccess
            foreach (array_merge($this->configFiles, ['.htaccess']) as $file) {
                if (file_exists("$scriptRoot/$file")) {
                    $this->docRoot = $scriptRoot;
                    break;
                }
            }
        }

        // Clean and store the requested path
        $this->reqPath = explode('?', Utils::getCleanPath(
            rawurldecode($_SERVER['REQUEST_URI'])
        ))[0];

        // Prepare user config
        $this->config = $this->getUserConfig();
        if (array_key_exists('namespaces', $this->config)) {
            $this->namespaces = $this->checkNamespaces($this->config['namespaces']);
        }
        if (array_key_exists('debug_mode', $this->config)) {
            $this->debugMode = (bool) $this->config['debug_mode'];
        }
        if (array_key_exists('allow_only', $this->config)) {
            if (is_array($a = $this->config['allow_only'])) {
                $this->allowOnly = $a;
            }
        }

        // Can we find the requested file?
        $finfo = $this->findRequestedFile($this->reqPath);
        $this->realPath = $finfo['path'];
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
            $this->showPage('500', [
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
                $this->showPage('500', [
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

        // We will look for "path(/index.html|/index.twig|.md|.twig)"
        $candidates = [];
        $pathExt = pathinfo($basePath, PATHINFO_EXTENSION);

        // create list of candidate file paths
        if (is_dir($basePath)) {
            $real = $basePath;
            $mode = 'dir'; // might be overwritten if we find one of the index files
            foreach ($this->indexFiles as $file) {
                $candidates[] = "$basePath/$file";
            }
        }
        else {
            $candidates[] = $basePath;
            foreach($this->renderExt as $renderExt) {
                if ($pathExt !== $renderExt) $candidates[] = "$basePath.$renderExt";
            }
        }

        // stop on first match and figure out some info about it
        foreach ($candidates as $candidate) {
            if (is_file($candidate) === false) continue;
            $real = $candidate;
            $realExt = pathinfo($real, PATHINFO_EXTENSION);
            $mode = 'file';
            if (in_array($realExt, $this->renderExt)) {
                $mode = $realExt === $pathExt ? 'source' : $realExt;
            }
            break;
        }
        return [
            'path' => $real,
            'mode' => $mode
        ];
    }

    /**
     * Check if a file should be blocked, using user config
     * @param string $filename - filename (or file path)
     * @return bool
     */
    private function allowFile($filename) {
        $name = pathinfo(strtolower($filename), PATHINFO_BASENAME);
        foreach($this->blockTypes as $pattern) {
            if (fnmatch($pattern, $name)) return false;
        }
        if (!is_array($this->allowOnly)) return true;
        $matches = array_filter($this->allowOnly, function($p) use ($name) {
            return is_string($p) && trim($p) !== '' && fnmatch($p, $name);
        });
        return count($matches) > 0;
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
                $this
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
        $mode = $this->renderMode;
        $realName = pathinfo($this->realPath, PATHINFO_BASENAME);
        $realExt = pathinfo($this->realPath, PATHINFO_EXTENSION);
        $textExt = ['md', 'mdown', 'markdown', 'txt'];

        // Override mode for forbidden file types
        if (!in_array($mode, ['dir', '404', '500']) && $this->allowFile($realName) === false) {
            $mode = '403';
        }
        // Or for some specific modes if browsing is disabled
        if ($this->debugMode === false) {
            // fall back to serving markdown/text as simple files (for complete URL only)
            if ($mode === 'source') {
                $mode = in_array($realExt, $textExt) ? 'file' : '404';
            }
            if ($mode === 'dir' || in_array($mode, $textExt)) {
                $mode = '404';
            }
        }

        // Now we're good to do the actual serving/rendering
        if ($mode === '403' || $mode === '404' || $mode === '500') {
            return $this->showError($mode);
        }
        if ($mode === 'file') {
            Utils::sendHeaders('200', '', $this->realPath);
            return readfile($this->realPath);
        }
        if (in_array($mode, ['md', 'markdown'])) {
            return $this->showText($this->realPath);
        }
        if ($mode === 'twig') {
            try {
                $templateId = str_replace($this->docRoot.'/', '', $this->realPath);
                $result = $this->twig()->renderUserTemplate($templateId);
                Utils::sendHeaders('200', 'text/html', $this->realPath);
                return $result;
            }
            catch (\Twig_Error $error) {
                return $this->showTwigError($error);
            }
        }
        if ($mode === 'source') {
            return $this->showSource($this->realPath);
        }
        if ($mode === 'dir') {
            return $this->showDir();
        }
        return '';
    }

    /**
     * Render an info or error page
     * @param string $statusCode HTTP status code
     * @param array $data Variables for the error template
     * @param bool $print Force printing the result instead of returning it
     * @return string
     */
    private function showPage($statusCode='404', $data=[], $print=false)
    {
        Utils::sendHeaders($statusCode, 'text/html');
        if ($this->debugMode === false) {
            $html = $this->limitedErrorPage($statusCode);
        } else {
            $html = $this->twig()->renderTwigExpressPage($data);
        }
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
        return $this->showPage('200', [
            'code' => Utils::formatCodeBlock($source, $lang !== 'md'),
            'codeLang' => $lang,
            'navBorder' => false
        ]);
    }

    /**
     * Show a simple text file, optionally rendered through Markdown
     * @param string $path
     * @param bool $markdown
     * @return string
     */
    private function showText($path, $markdown=true)
    {
        $source = file_get_contents($path);
        $content = $markdown ? Utils::processMarkdown($source) : nl2br($source);
        return $this->showPage('200', ['content' => $content]);
    }

    /**
     * Prepare data for a 403/404/500 page
     * @param string $code
     * @return string
     */
    private function showError($code='404')
    {
        $title = 'File not found';
        $verb = 'Could not find';
        if ($code === '403') {
            $title = 'Forbidden';
            $verb = 'Access restricted';
        }
        if ($code === '500') {
            $title = 'Error';
            $verb = 'Could not display';
        }
        $path = $this->reqPath;
        if ($path !== '/') $path = trim($path, '/');
        $msg  = "$verb: <code class=\"error\">$path</code><br>\n";
        $msg .= "Document root: <code>$this->docRoot</code>";
        return $this->showPage($code, [
            'title' => $title,
            'message' => $msg
        ]);
    }

    /**
     * Error page template with a single message
     * @param string $code
     * @return string
     */
    private function limitedErrorPage($code='404') {
        $path = $this->reqPath;
        if ($path !== '/') $path = rtrim($path, '/');
        $msg = $code === '500' ? 'Error' : 'File not found';
        return "<title>$code - $path</title><style>"
            . 'body{display:flex;height:100%;margin:0;align-items:center;color:#222;background:#eee}'
            . 'p{width:100%;margin:0;padding:2em;text-align:center;font-family:sans-serif}'
            . 'code{display:block;padding:.5em;font-family:monospace,monospace;font-size:120%;color:#A00}'
            . "</style><body><p>$msg<br><code>$path</code></p></body>"
            ;
    }

    /**
     * Show a Twig file with syntax highlighting
     * @return string
     */
    private function showDir()
    {
        $root = $this->realPath;
        // Collapse multiple slashes (we could end up with '///', collapsed to '/')
        $base = Utils::getCleanPath('/' . $this->reqPath . '/');
        $fileList = [];
        $dirList = [];

        foreach(Utils::getFileList('*', $root, 'file') as $name) {
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $url = $base . $name;
            // skip dotfiles and blocked file types
            if (substr($name, 0, 1) === '.' || $this->allowFile($name) === false) {
                continue;
            }
            if (in_array($ext, $this->renderExt)) {
                $url = $base . pathinfo($name, PATHINFO_FILENAME);
            }
            $fileList[] = ['name' => $name, 'url' => $url];
        }
        foreach(Utils::getFileList('*', $root, 'dir') as $name) {
            // skip dotfiles
            if (substr($name, 0, 1) === '.') continue;
            $dirList[] = ['name' => $name, 'url' => $base.$name];
        }

        $message = '';
        if (count($dirList) + count($fileList) === 0) {
            $message = 'Empty directory';
        }
        return $this->showPage('200', [
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

        return $this->showPage('500', $data);
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
        $path = trim($this->reqPath, '/');
        if ($path === '') $path .= '/';

        // Return docroot folder name (or parent/folder, if short) as site name
        $folder = basename($this->docRoot);
        if (strlen($folder) <= 5) {
            $folder = basename(dirname($this->docRoot)) . ':' . $folder;
        }
        $pathBn = basename($path);
        $pathFn = pathinfo($path, PATHINFO_FILENAME);
        $real = pathinfo($this->realPath, PATHINFO_BASENAME);

        // We should not use the breadcrumbs or its parent layout at all when
        // debug mode is off, but let’s restrict info anyway
        if ($this->debugMode === false) {
            return [
                'title' => $pathBn,
                'crumbs' => []
            ];
        }

        // Results, which we'll increment over time
        $url = '/';
        $crumbs = [
            ['url' => $url, 'name' => $folder, 'ext' => false]
        ];

        $fragments = array_filter(explode('/', $path));
        // showing an index file but the URL path has the directory name only?
        if (in_array($real, $this->indexFiles) && $pathFn !== 'index') {
            $last = $real;
        }
        // otherwise treat last path fragment as the actual last item
        // (whether it's a folder or file)
        else {
            $last = array_pop($fragments);
        }
        foreach ($fragments as $f) {
            $url .= $f . '/';
            $crumbs[] = ['url' => $url, 'name' => $f, 'ext' => false];
        }
        $active = count($crumbs) - 1;
        // Add last item (sometimes as two separate items for filename/extension)
        if ($last) {
            $path_ext = pathinfo($path, PATHINFO_EXTENSION);
            $real_ext = pathinfo($real, PATHINFO_EXTENSION);
            // Static files, or 404/403
            if ($real && in_array($real_ext, $this->renderExt) && $this->allowFile($real)) {
                $active += $real_ext === $path_ext ? 2 : 1;
                $real_noext = pathinfo($real, PATHINFO_FILENAME);
                $crumbs[] = ['url' => $url.$real_noext, 'name' => $real_noext, 'ext' => false];
                $crumbs[] = ['url' => $url.$real, 'name' => '.'.$real_ext, 'ext' => true];
            }
            else {
                $active += 1;
                $crumbs[] = ['url' => $url.$last, 'name' => $last, 'ext' => false];
            }
        }
        // Add 'active' attribute to items
        for ($i=0, $max=count($crumbs); $i < $max; $i++) {
            $crumbs[$i]['active'] = $i === $active;
        }
        return $this->navInfo = [
            'title' => ($pathBn ? $pathBn . ' - ' : '') . $folder,
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
        $root = __DIR__ . '/tpl/';
        return $this->layoutAssets = [
            'css' => file_get_contents($root . 'layout.min.css'),
            'js'  => file_get_contents($root . 'layout.min.js')
        ];
    }
}
