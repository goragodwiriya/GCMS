function initEDocumentMain(id) {
  var patt = /^(icon\-)?(download|delete)\s([0-9]+)$/;
  var doDownloadClick = function() {
    var hs = patt.exec(this.className);
    if (hs) {
      if (
        hs[2] == "delete" &&
        !confirm(trans("You want to XXX ?").replace(/XXX/, trans("delete")))
      ) {
        return false;
      }
      var req = new GAjax({
        asynchronous: false
      });
      req.send(
        WEB_URL + "index.php/edocument/model/download/action",
        "action=" + hs[2] + "&id=" + this.className
      );
      var ds = req.responseText.toJSON();
      if (ds) {
        if (ds.confirm) {
          if (confirm(ds.confirm)) {
            req.send(
              WEB_URL + "index.php/edocument/model/download/action",
              "action=downloading&id=" + this.className
            );
            ds = req.responseText.toJSON();
            if (ds.id) {
              if (ds.target && ds.target == 1) {
                this.set("target", "download");
              }
              if (ds.href) {
                this.href = ds.href;
              }
              return true;
            }
          }
        }
        if (ds.action == "delete" && $E("edocument_" + ds.id)) {
          $G("edocument_" + ds.id).remove();
        }
        if (ds.alert) {
          alert(ds.alert);
        }
      } else if (req.responseText != "") {
        alert(req.responseText);
      }
    }
    return false;
  };
  forEach($G(id).elems("a"), function() {
    if (patt.test(this.className)) {
      callClick(this, doDownloadClick);
    }
  });
}
