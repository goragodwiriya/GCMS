function initGalleryView(id) {
  var patt = /preview_([0-9]+)/;
  $G(window).Ready(function() {
    if (G_Lightbox === null) {
      G_Lightbox = new GLightbox();
    } else {
      G_Lightbox.clear();
    }
    forEach($E(id).getElementsByTagName("a"), function() {
      if (patt.test(this.id)) {
        G_Lightbox.add(this);
      }
    });
  });
}
