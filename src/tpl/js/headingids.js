try { for (const h of document.querySelectorAll('.Content h1, .Content h2, .Content h3')) {
  if (h.hasAttribute('id')) continue
  h.setAttribute('id', h.textContent
    .trim().toLowerCase()
    .replace(/[^\w\d]+/g, ' ')
    .replace(/\s+/g, '-'))
}} catch(e) {}
