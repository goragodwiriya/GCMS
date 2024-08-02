function initDocCat(id) {
  var patt = /icon-(expand|collapse)/;
  var _doClick = function() {
    if (patt.test(this.className)) {
      this.className =
        this.className == "icon-collapse" ? "icon-expand" : "icon-collapse";
    }
  };
  forEach($G(id).elems("span"), function() {
    callClick(this, _doClick);
  });
}

function initDocView(id, cat) {
  var node = $E("doccat_" + id + "_" + cat);
  if (node) {
    if (id == 0) {
      node.className = "icon-collapse";
    } else {
      var topNode = $E("doccat_" + 0 + "_" + cat);
      topNode.className = "icon-collapse";
      node.parentNode.className = "select";
    }
  }
}
