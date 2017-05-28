Getting Started with Twig
=========================


Let’s learn some Twig!

In addition to this introduction, you should keep the [Twig documentation][TWIG_DOC] around to check what the available Twig tags, filters and functions are.


Using variables
---------------

Creating and using variables is simple:

```twig
{# Hey I’m a comment! On the next few lines, you can see
   a Twig tag and the syntax for outputting content. #}
{% set pageTitle = 'Cool title!' %}
<h1>{{ pageTitle }}</h1>
```

Sometimes a variable may contain some HTML code, or HTML entitites, and by default Twig will escape this. If you want to use unescaped content, use the `raw` filter:

```twig
{% set pageTitle = '<em>Awesome</em> title!' %}
<h1>{{ pageTitle|raw }}</h1>
```

Be careful *not* to do this for content that you don’t controll (e.g. any user-submitted content, content from GET and POST parameters, etc.).

Finally, sometimes a variable may or may not be defined. To avoid getting an error, you can use the `default` filter:

```twig
{# Define default values when using variables #}
<h1>{{ pageTitle|default('No title') }}</h1>
{% for i in 1..numItems|default(10) %}
  {{ i }}<br>
{% endfor %}

{# Or define default values beforehand, at the start of your template maybe #}
{% set pageTitle = pageTitle|default('No title') %}
{% set numItems = numItems|default(10) %}
...
<h1>{{ pageTitle }}</h1>
{% for i in 1..numItems %}
  {{ i }}<br>
{% endfor %}
```


Including stuff
---------------

Many people have dabbled in PHP before, writing `<?php … ?>` tags just for one thing: the `include()` function. That’s also a perfectly good reason for using Twig. :)

With TwigExpress, all your includes start from the root folder. So if you have this structure:

```
twigexpress.phar
blocks/
    header.twig
section1/
    page1.twig
    page2.twig
```

When using the `{% include %}` tag, you will have to give the complete path from the root folder:

```twig
{# Yes! #}
{% include 'blocks/header.twig' %}
{% include 'section1/page1.twig' %}

{# Nope. Relative paths won't work, sorry! #}
{% include 'page2.twig' %}
{% include '../blocks/header.twig' %}
```

With Twig, you can include a template and pass it some variables:

```twig
{% include 'blocks/articleteaser.twig' with {
    title: 'Awesome title',
    image: 'assets/img/teaser/1.jpg',
    text: 'Lorem ipsum dolor sit amet, consectetur adipisicing elit…'
} %}
```

Note that these examples we used the `include` tag. There’s also a similar `include` function if you like that syntax better:

```twig
{{ include('blocks/header.twig') }}
{{ include('blocks/articleteaser.twig', {title: 'Awesome title'}) }}
```

You can also include the content of a file without rendering it as a Twig template. This can be quite useful for content that is *not* a Twig template (especially CSS, JS or JSON content). For that, use the `source` function.

```twig
<script>
    {{ source('assets/js/analytics.js') }}
</script>
```


Extending a parent template
---------------------------

Next up is the topic of blocks and extends. They’re a really powerful tool, especially for building HTML pages. Rather than describing it here, we’ll refer you to [Twig’s introduction to template inheritance][TWIG_INHERITANCE].

That’s it, you know everything you need to get started with Twig!


[TWIG_DOC]: http://twig.sensiolabs.org/documentation
[TWIG_INHERITANCE]: http://twig.sensiolabs.org/doc/templates.html#template-inheritance
