<?php

if (!isset($isError)) $isError = false;

if (empty($title)) $title = '';
$title = str_replace('Twig_Error_', 'Twig Error&hairsp;: ', $title);

// For Twig source views
if (!empty($url) && !empty($base)) {
    $breadcrumbs = '';
    $bcPath = ltrim(preg_replace('/\.twig$/', '/.twig', $url), '/');
    $parts = array_filter(explode('/', $bcPath));
    $baseName = $_SERVER['HTTP_HOST'] . rtrim($base, '/');
    if (count($parts) == 0) {
        $breadcrumbs .= "<span class=\"item\">$baseName</span>";
    } else {
        $breadcrumbs .= "<a class=\"item\" href=\"$base\">$baseName</a><span>/</span>";
    }
    for ($i = 0, $max = count($parts); $i < $max; $i++) {
        $name = $parts[$i];
        if ($i > 0 && strpos($name, '.') !== 0) {
            $breadcrumbs .= '<span>/</span>';
        }
        if ($i !== $max - 1) {
            $link = $base . implode('/', array_slice($parts, 0, $i + 1));
            $breadcrumbs .= "<a class=\"item\" href=\"$link\">$name</a>";
        }
        else {
            $breadcrumbs .= "<span class=\"item\" >$name</span>";
            if ($isError && strpos($title, 'Twig') !== false) {
                $link = $base . implode('/', array_slice($parts, 0, $i + 1)) . '.twig';
                $breadcrumbs .= "<a class=\"item\" href=\"$link\">.twig</a>";
            }
        }
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <title><?php echo $title ?></title>
    <style><?php
        echo file_get_contents(__DIR__ . '/styles.css');
    ?></style>
</head>
<body>
    <header>
        <nav><?php echo $breadcrumbs; ?></nav>
        <?php if (!empty($title)) { echo "<h1>$title</h1>\n"; } ?>
        <?php if (!empty($message)) { echo "<p>$message</p>\n"; } ?>
        <?php if (!empty($file)) { echo "<p><code>$file</code></p>\n"; } ?>
    </header>
    <?php if (!empty($code)): ?>
    <main>
        <pre><code class="twig<?php
            if ($isError) echo ' markError';
            if (strpos($code, '<span data-num') !== false) echo ' lineNumbers';
        ?>"><?php echo $code ?></code></pre>
    </main>
    <script>
    <?php
        // Try to figure out the subLanguage
        $subLang   = 'xml';
        $subLangs  = [
            'xml'  => 'xml',
            'html' => 'xml',
            'htm'  => 'xml',
            'json' => 'json',
            'js'   => 'javascript',
            'css'  => 'css',
            'md'   => 'markdown',
            'mdown' => 'markdown',
            'markdown' => 'markdown'
        ];
        $ext = pathinfo(preg_replace('/\.twig$/', '', strtolower($url)), PATHINFO_EXTENSION);
        if (array_key_exists($ext, $subLangs)) {
            $subLang = $subLangs[$ext];
        }
        echo "window.twigSubLanguage = '$subLang';\n";

        // Include the script
        $highlight = file_get_contents(__DIR__ . '/highlight.min.js');
        echo str_replace('</script', '<\\/script', $highlight) . "\n";
        echo ";hljs.initHighlighting();\n";
    ?>
    </script>
    <?php endif; ?>
</body>
</html>
