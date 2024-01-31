/**
 * Javascript Libraly for GCMS (front-end)
 *
 * @filesource js/gcms.js
 * @link https://www.kotchasan.com/
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 */
function initSearch(form, input, module) {
  var doSubmit = function(e) {
    input = $G(input);
    var v = input.value.trim();
    if (v.length < 2) {
      input.invalid();
      alert(input.title);
      input.focus();
    } else {
      loaddoc(
        WEB_URL +
        "index.php?module=" +
        $E(module).value +
        "&q=" +
        encodeURIComponent(v)
      );
    }
    GEvent.stop(e);
    return false;
  };
  $G(form).addEvent("submit", doSubmit);
}

function getCurrentURL() {
  var patt = /^(.*)=(.*)$/;
  var patt2 = /^[0-9]+$/;
  var urls = new Object();
  var u = window.location.href;
  var us2 = u.split("#");
  u = us2.length == 2 ? us2[0] : u;
  var hs,
    us1 = u.split("?");
  u = us1.length == 2 ? us1[0] : u;
  if (us1.length == 2) {
    forEach(us1[1].split("&"), function() {
      hs = patt.exec(this);
      if (hs) {
        urls[hs[1].toLowerCase()] = this;
      } else {
        urls[this] = this;
      }
    });
  }
  if (us2.length == 2) {
    forEach(us2[1].split("&"), function() {
      hs = patt.exec(this);
      if (hs) {
        if (MODULE_URL == "1" && hs[1] == "module") {
          if (hs[2] == FIRST_MODULE) {
            u = WEB_URL + "index.php";
          } else {
            u = WEB_URL + hs[2].replace(/\-/g, "/") + ".html";
          }
        } else if (hs[1] != "visited") {
          urls[hs[1].toLowerCase()] = this;
        }
      } else if (!patt2.test(this)) {
        urls[this] = this;
      }
    });
  }
  var us = new Array();
  for (var p in urls) {
    if (p != 'fbclid') {
      us.push(urls[p]);
    }
  }
  if (us.length > 0) {
    u += "?" + us.join("&");
  }
  return u;
}

function initIndex(id) {
  $G(window).Ready(function() {
    if (G_Lightbox === null) {
      G_Lightbox = new GLightbox();
    } else {
      G_Lightbox.clear();
    }
    var content = $G(id || "content");
    forEach(content.elems("img"), function(item) {
      if (!$G(item).hasClass("nozoom")) {
        new preload(item, function() {
          if (floatval(this.width) > floatval(item.width)) {
            G_Lightbox.add(item);
          }
        });
      }
    });
    forEach(content.getElementsByClassName("copytoclipboard"), function() {
      callClick(this, function() {
        var element = this.nextSibling;
        if (document.selection) {
          var range = document.body.createTextRange();
          range.moveToElementText(element);
          range.select();
        } else if (window.getSelection) {
          var range = document.createRange();
          range.selectNode(element);
          window.getSelection().removeAllRanges();
          window.getSelection().addRange(range);
        }
        document.execCommand("copy");
        document.body.msgBox(trans("successfully copied to clipboard"));
      });
    });
  });
}

function changeLanguage(lang) {
  $G(window).Ready(function() {
    forEach(lang.split(","), function() {
      $G("lang_" + this).addEvent("click", function(e) {
        GEvent.stop(e);
        window.location = replaceURL({lang: this.title});
      });
    });
  });
}
var doLogout = function(e) {
  setQueryURL("action", "logout");
};
var doMember = function(e) {
  GEvent.stop(e);
  var action = $G(this).id;
  if (this.hasClass("register")) {
    action = "register";
  } else if (this.hasClass("forgot")) {
    action = "forgot";
  }
  showModal(
    WEB_URL + "xhr.php",
    "class=Index\\Member\\Controller&method=modal&action=" + action
  );
  return false;
};

function setQueryURL(key, value) {
  var a = new Array();
  var patt = new RegExp(key + "=.*");
  var ls = window.location.toString().split(/[\?\#]/);
  if (ls.length == 1) {
    window.location = ls[0] + "?" + key + "=" + value;
  } else {
    forEach(ls[1].split("&"), function(item) {
      if (!patt.test(item)) {
        a.push(item);
      }
    });
    var url =
      ls[0] +
      "?" +
      key +
      "=" +
      value +
      (a.length == 0 ? "" : "&" + a.join("&"));
    if (key == "action" && value == "logout") {
      window.location = url;
    } else {
      loaddoc(url);
    }
  }
}

function loaddoc(url) {
  if (loader && url != WEB_URL) {
    loader.location(url);
  } else {
    window.location = url;
  }
}

function getWidgetNews(id, module, interval, callback) {
  var req = new GAjax();
  var _callback = function(xhr) {
    if (xhr.responseText !== "") {
      if ($E(id)) {
        var div = $G(id);
        div.setHTML(xhr.responseText);
        if (Object.isFunction(callback)) {
          callback.call(div);
        }
        if (loader) {
          loader.init(div);
        }
      } else {
        req.abort();
      }
    }
  };
  var _getRequest = function() {
    return (
      "class=Widgets\\" +
      module +
      "\\Controllers\\Index&method=getWidgetNews&id=" +
      id
    );
  };
  if (interval == 0) {
    req.send(WEB_URL + "xhr.php", _getRequest(), _callback);
  } else {
    req.autoupdate(
      WEB_URL + "xhr.php",
      floatval(interval),
      _getRequest,
      _callback
    );
  }
}

function initWidgetTab(tab, id, module, category) {
  var patt = /tab_([0-9,]+)/;

  function getNews(wait, category) {
    var q =
      "class=Widgets\\" +
      module +
      "\\Controllers\\Index&method=getWidgetNews&id=" +
      id +
      "&cat=" +
      category;
    send(
      WEB_URL + "xhr.php",
      q,
      function(xhr) {
        $E(id).innerHTML = xhr.responseText;
      },
      wait
    );
  }
  var _tabClick = function() {
    var temp = this;
    getNews(this, this.id.replace("tab_", ""));
    forEach($G(tab).elems("a"), function() {
      if (temp == this) {
        this.addClass("select");
      } else {
        $G(this).removeClass("select");
      }
    });
  };
  if (tab != "" && $E(tab)) {
    var firstitem = null;
    forEach($G(tab).elems("a"), function(item, index) {
      if (patt.test(item.id)) {
        callClick(item, _tabClick);
        if (index == 0) {
          firstitem = item;
        }
      }
    });
    if (firstitem) {
      _tabClick.call(firstitem);
    }
  } else {
    getNews("wait", category);
  }
}
var G_editor = null;

function initEditor(frm, editor, action) {
  $G(window).Ready(function() {
    if ($E(editor)) {
      G_editor = editor;
      new GForm(frm, action).onsubmit(doFormSubmit);
    }
  });
}

function initDocumentView(id, module) {
  $G(id).Ready(function() {
    var patt = /(quote|edit|delete|pin|lock|print|pdf)-([0-9]+)-([0-9]+)-([0-9]+)-(.*)$/;
    var viewAction = function(action) {
      var temp = this;
      send(
        WEB_URL + "xhr.php/" + module + "/model/action/view",
        action,
        function(xhr) {
          var ds = xhr.responseText.toJSON();
          if (ds) {
            if (ds.action == "quote") {
              var editor = $E(G_editor);
              if (editor && ds.detail !== "") {
                editor.value = editor.value + ds.detail;
                editor.focus();
              }
            } else if (
              (ds.action == "pin" || ds.action == "lock") &&
              $E(ds.action + "_" + ds.qid)
            ) {
              var a = $E(ds.action + "_" + ds.qid);
              a.className = a.className.replace(
                /(un)?(pin|lock)\s/,
                (ds.value == 0 ? "un" : "") + "$2 "
              );
              a.title = ds.title;
            }
            if (ds.confirm) {
              if (confirm(ds.confirm)) {
                if (ds.action == "deleting") {
                  viewAction.call(
                    temp,
                    "id=" + temp.className.replace("delete-", "deleting-")
                  );
                }
              }
            }
            if (ds.alert) {
              alert(ds.alert);
            }
            if (ds.location) {
              loaddoc(ds.location.replace(/&amp;/g, "&"));
            }
            if (ds.remove && $E(ds.remove)) {
              $G(ds.remove).remove();
            }
          } else if (xhr.responseText != "") {
            console.log(xhr.responseText);
          }
        },
        this
      );
    };
    var viewExport = function(action) {
      var hs = patt.exec(action);
      window.open(
        WEB_URL +
        "export.php?action=" +
        hs[1] +
        "&id=" +
        hs[2] +
        "&module=" +
        hs[5],
        "print"
      );
    };
    forEach($G(id).elems("a"), function(item, index) {
      if (patt.exec(item.className)) {
        callClick(item, function() {
          var hs = patt.exec(this.className);
          if (hs[1] == "print" || hs[1] == "pdf") {
            viewExport(this.className);
          } else {
            viewAction.call(this, "id=" + this.className);
          }
        });
      }
    });
    initIndex(id);
  });
}
$G(window).Ready(function() {
  initWeb("");
});
