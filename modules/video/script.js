function initVideoList(id) {
  var patt = /^youtube_([0-9]+)_([a-zA-Z0-9\-_]{11,11})$/;
  forEach($G(id).elems("a"), function() {
    if (patt.test(this.id)) {
      callClick(this, function() {
        showModal(
          WEB_URL + "xhr.php",
          "class=Video\\View\\Controller&method=modal&id=" + this.id
        );
        return false;
      });
    }
  });
}
