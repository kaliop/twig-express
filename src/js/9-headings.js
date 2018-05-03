(function() {
  var selector = ".Content h1,.Content h2,.Content h3";
  var headings = document.querySelectorAll(selector);
  for (var i = 0; i < headings.length; i++) {
    var h = headings[i];
    if (h.hasAttribute("id")) continue;
    h.setAttribute(
      "id",
      h.textContent
        .trim()
        .toLowerCase()
        .replace(/[^\w\d]+/g, " ")
        .replace(/[\s_\-]+/g, "-")
    );
  }
})();
