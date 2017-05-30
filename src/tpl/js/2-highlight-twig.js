/*
 Language: Twig
 Requires: xml.js
 Author: Luke Holder <lukemh@gmail.com>
 Description: Twig is a templating language for PHP
 Category: template
 */

hljs.registerLanguage('twig', function (hljs) {
  var PARAMS = {
    className: 'params',
    begin: '\\(', end: '\\)'
  };

  var CUSTOM_FUNCTIONS = 'param lorem markdown files folders';
  var FUNCTION_NAMES = CUSTOM_FUNCTIONS + ' attribute block constant ' +
    'cycle date dump include max min parent random range source ' +
    'template_from_string';

  var FUNCTIONS = {
    beginKeywords: FUNCTION_NAMES,
    keywords: {name: FUNCTION_NAMES},
    relevance: 0,
    contains: [
      PARAMS
    ]
  };

  var FILTER = {
    begin: /\|[A-Za-z_]+:?/,
    keywords:
    'abs batch capitalize convert_encoding date date_modify default ' +
    'escape first format join json_encode keys last length lower ' +
    'merge nl2br number_format raw replace reverse round slice sort split ' +
    'striptags title trim upper url_encode',
    contains: [
      FUNCTIONS
    ]
  };

  var TAGS = 'autoescape block do else elseif embed extends filter flush for ' +
    'if import include macro sandbox set spaceless use verbatim'
      .split(' ').map(function (t) {
      return t + ' end' + t
    }).join(' ');

  return {
    case_insensitive: true,
    subLanguage: window.twigSubLanguage || 'xml',
    contains: [
      {
        className: 'template-comment',
        begin: /\{#/, end: /#}/,
        contains: []
      },
      {
        className: 'template-tag',
        begin: /\{%/, end: /%}/,
        contains: [
          {
            className: 'name',
            begin: /\w+/,
            keywords: TAGS,
            starts: {
              endsWithParent: true,
              contains: [FILTER, FUNCTIONS],
              relevance: 0
            }
          }
        ]
      },
      {
        className: 'template-variable',
        begin: /\{\{/, end: /}}/,
        contains: ['self', FILTER, FUNCTIONS]
      }
    ]
  };
});
