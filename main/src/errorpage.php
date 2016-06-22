<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <title><?php echo $title ?></title>
    <style>
    body {
        background-color: #fff;
        color: #333;
        margin: 0;
        font-family: sans-serif;
        line-height: 1.5;
    }
    div {
        padding: 1rem 1.5rem;
    }
    h1 {
        margin: .5rem 0 1rem;
        font-size: 140%;
        line-height: 1.2;
    }
    h2 {
        margin: -.75rem 0 1rem;
        font-size: 100%;
        font-weight: normal;
        font-family: monospace, monospace;
        line-height: 1.3;
        color: #909090;
    }
    p {
        margin: .5rem 0;
    }
    h1 + p, h2 + p {
        margin-top: 1em;
        padding-top: 1em;
        border-top: 1px dashed #ddd;
    }
    pre {
        margin: 0;
        padding: 1.5rem;
        white-space: pre-wrap;
        -webkit-tab-size: 4;
        -moz-tab-size: 4;
        tab-size: 4;
        color: #999;
        background: black;
    }
    code {
        font-size: 100%;
        font-family: Consolas, Menlo, Source Code Pro, DejaVu Sans Mono, monospace;
    }
    pre code {
        display: block;
        font-size: 85%;
    }
    pre mark {
        color: white;
        background: none;
    }

    @media (min-width: 40em) {
        html {
            padding: 1em;
            background-color: #eee;
        }
        body {
            width: 48em;
            max-width: 100%;
            margin: 1em auto;
            border: solid 1px #ccc;
        }
    }

    @media (max-width: 29.99em) {
        div {
            padding: 1.5rem 1rem;
        }
        pre {
            padding: 1.5rem 1rem;
            overflow-x: auto;
        }
        pre code {
            padding-left: 0;
        }
    }

    @media (min-width: 30em) {
        [data-line] {
            display: inline-block;
            box-sizing: border-box;
            width: 100%;
            padding-left: 6ch;
        }
        [data-line]::before {
            content: attr(data-line);
            display: inline-block;
            box-sizing: border-box;
            width: 5ch;
            margin-left: -7ch;
            padding-right: 2ch;
            text-align: right;
            opacity: .6;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        mark[data-line]::before {
            opacity: .75;
        }
    }
    </style>
</head>
<body>
    <div>
        <h1><?php echo $title ?></h1>
        <?php if (!empty($file)) { echo "<h2>$file</h2>\n"; } ?>
        <?php if (!empty($message)) { echo "<p>$message</p>\n"; } ?>
    </div>
    <?php if (!empty($code)) { echo "<pre><code>$code</code></pre>"; } ?>
</body>
</html>