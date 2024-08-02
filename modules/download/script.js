var doDownloadClick = function() {
  var req = new GAjax({
    asynchronous: false
  });
  req.send(
    WEB_URL + "index.php/download/model/download/action",
    "action=download&id=" + this.id
  );
  var ds = req.responseText.toJSON();
  if (ds) {
    if (ds.confirm) {
      if (confirm(ds.confirm)) {
        req.send(
          WEB_URL + "index.php/download/model/download/action",
          "action=downloading&id=" + this.id
        );
        ds = req.responseText.toJSON();
        if (ds.downloads && $E("downloads_" + ds.id)) {
          $E("downloads_" + ds.id).innerHTML = ds.downloads;
        }
        if (ds.href) {
          this.href = ds.href;
          return true;
        }
      }
    }
    if (ds.alert) {
      alert(ds.alert);
    }
  } else if (req.responseText != "") {
    alert(req.responseText);
  }
  return false;
};

function initDownloadList(id) {
  var patt = /download_([0-9]+)/;
  if (id) {
    var e = $E(id);
    if (e.tagName.toLowerCase() == "a" && patt.test(e.id)) {
      callClick(e, doDownloadClick);
    } else {
      forEach($G(id).elems("a"), function() {
        if (patt.test(this.id)) {
          callClick(this, doDownloadClick);
        }
      });
    }
  }
  var categoryChanged = function() {
    loaddoc(this.value);
  };
  if ($E("download-cat")) {
    $G("download-cat").addEvent("change", categoryChanged);
  }
}
