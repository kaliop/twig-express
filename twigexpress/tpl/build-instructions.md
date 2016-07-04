We’re making a custom build of highlight.js with customized Twig support.

## Updating the highlight-main.js file

Mostly to keep up with updates, so no need to do that every time.

    git clone https://github.com/isagalaev/highlight.js.git
    cd highlight.js
    node tools/build.js -n css xml javascript json markdown

Then copy `highlight.js/build/highlight.pack.js` as highlight-main.js.

## Building the highlight.min.js file

Do this every time you change highlight-twig.js

    npm install -g uglify-js
    uglifyjs highlight-main.js highlight-twig.js > highlight.min.js
