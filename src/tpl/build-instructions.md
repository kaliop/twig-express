# Assets build tasks

- Minify CSS
- Minify JS
- Custom Highlight.js build

## Minify CSS and JS

```
npm install && npm run build
```

## Updating the highlight-main.js file

Mostly to keep up with updates, so no need to do that every time.

    git clone https://github.com/isagalaev/highlight.js.git
    cd highlight.js
    node tools/build.js -n css xml javascript json markdown

Then copy `highlight.js/build/highlight.pack.js` as highlight-main.js.
