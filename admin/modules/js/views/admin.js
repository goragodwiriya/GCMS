var COLORS = [
  "#7E57C2",
  "#FF5722",
  "#E91E63",
  "#259B24",
  "#607D8B",
  "#2CB6D5",
  "#FD971F",
  "#26A694",
  "#FF5722",
  "#00BCD4",
  "#8BC34A",
  "#616161",
  "#FFD54F",
  "#03A9F4",
  "#795548"
];

function initEditInplace(id, className) {
  var editor,
    hs,
    patt = /config_status_(delete|name|color)_([0-9]+)/;

  function _doAction(c) {
    var q = "";
    if (this.id == id + "_add") {
      q = "action=" + this.id;
    } else if ((hs = patt.exec(this.id))) {
      if (
        hs[1] == "delete" &&
        confirm(trans("You want to XXX ?").replace(/XXX/, trans("delete")))
      ) {
        q = "action=" + this.id;
      } else if (hs[1] == "color") {
        q = "action=" + this.id + "&value=" + encodeURIComponent(c);
      }
    }
    if (q != "") {
      send(
        "index.php/" + className,
        q,
        function(xhr) {
          var ds = xhr.responseText.toJSON();
          if (ds) {
            if (ds.data) {
              $G(id).appendChild(ds.data.toDOM());
              _doInitEditInplaceMethod(ds.newId);
              $E(ds.newId.replace("status", "status_name")).focus();
            } else if (ds.del) {
              $G(ds.del).remove();
            } else if (ds.edit) {
              hs = patt.exec(ds.editId);
              if (hs[1] == "color") {
                c = ds.edit;
                $E(ds.editId).title = trans("change color") + " (" + c + ")";
                $E(ds.editId.replace("color", "name")).style.color = c;
              }
            }
            if (ds.alert) {
              alert(ds.alert);
            }
          } else if (xhr.responseText != "") {
            alert(xhr.responseText);
          }
        },
        this
      );
    }
  }
  var o = {
    onSave: function(v, editor) {
      var req = new GAjax({
        asynchronous: false
      });
      req.initLoading(editor, false);
      req.send(
        "index.php/" + className,
        "action=" + this.id + "&value=" + encodeURIComponent(v)
      );
      var ds = req.responseText.toJSON();
      if (ds) {
        if (ds.alert) {
          alert(ds.alert);
        }
        if (ds.edit) {
          $E(ds.editId).innerHTML = ds.edit;
        }
        return true;
      } else if (req.responseText != "") {
        alert(req.responseText);
      }
      return false;
    }
  };

  function _doInitEditInplaceMethod(id) {
    var loading = true;
    forEach($G(id).elems("*"), function() {
      var hs = patt.exec(this.id);
      if (hs) {
        if (hs[1] == "delete") {
          callClick(this, _doAction);
        } else if (hs[1] == "color") {
          var t = this.title;
          this.title = trans("change color") + " (" + t + ")";
          new GDDColor(this, function(c) {
            $E(this.input.id.replace("color", "name")).style.color = c;
            if (!loading) {
              _doAction.call(this.input, c);
            }
          }).setColor(t);
        } else if (hs[1] == "name") {
          editor = new EditInPlace(this, o);
        }
      }
    });
    loading = false;
  }
  callClick(id + "_add", _doAction);
  _doInitEditInplaceMethod(id);
}

function initLanguages(id) {
  var patt = /^(edit|delete|check|import)_([a-z]{2,2})$/;
  var doClick = function() {
    var hs = patt.exec(this.id);
    var q = "";
    if (hs[1] == "check") {
      this.className =
        this.className == "icon-uncheck" ? "icon-check" : "icon-uncheck";
      var chs = [];
      forEach($E(id).getElementsByTagName("span"), function() {
        var cs = patt.exec(this.id);
        if (cs && cs[1] == "check" && this.className == "icon-check") {
          chs.push(this.id);
        }
      });
      if (chs.length == 0) {
        alert(trans("Please select at least one item"));
      } else {
        q = "action=changed&data=" + chs.join(",");
      }
    } else if (
      hs[1] == "delete" &&
      confirm(trans("You want to XXX ?").replace(/XXX/, trans("delete")))
    ) {
      q = "action=droplang&data=" + hs[2];
    } else if (
      hs[1] == "import" &&
      confirm(trans("You want to XXX ?").replace(/XXX/, this.title))
    ) {
      q = "action=import";
    }
    if (q != "") {
      send("index.php/index/model/languages/action", q, doFormSubmit, this);
    }
  };
  forEach($E(id).getElementsByTagName("span"), function() {
    if (patt.test(this.id)) {
      callClick(this, doClick);
    }
  });
  callClick("import_xx", doClick);
  new GDragDrop(id, {
    dragClass: "icon-move",
    endDrag: function() {
      var trs = [];
      forEach($E(id).getElementsByTagName("li"), function() {
        if (this.id) {
          trs.push(this.id);
        }
      });
      if (trs.length > 1) {
        send(
          "index.php/index/model/languages/action",
          "action=move&data=" + trs.join(","),
          doFormSubmit
        );
      }
    }
  });
}

function initMailserver() {
  var doChanged = function() {
    var a = this.value.toInt();
    $E("email_SMTPSecure").disabled = a == 0;
    $E("email_Username").disabled = a == 0;
    $E("email_Password").disabled = a == 0;
  };
  var el = $G("email_SMTPAuth");
  el.addEvent("change", doChanged);
  doChanged.call(el);
}

function initSystem() {
  var clearCache = function() {
    send(
      "index.php/index/model/system/clearCache",
      "action=clearcache",
      doFormSubmit,
      this
    );
  };
  callClick("clear_cache", clearCache);
  new Clock("local_time");
  new Clock("server_time");
}

function initMenuwrite() {
  var getMenus = function() {
    var t = $E("type").value;
    var sel = $E("menu_order");
    for (var i = sel.options.length - 1; i >= 0; i--) {
      sel.removeChild(sel.options[i]);
    }
    var q =
      "action=get&parent=" +
      $E("parent").value +
      "&id=" +
      $E("id").value.toInt();
    send("index.php/index/model/menuwrite/action", q, function(xhr) {
      var id = $E("id").value.toInt();
      var option = sel.options[0];
      var ds = xhr.responseText.toJSON();
      if (ds) {
        for (prop in ds) {
          q = prop.replace("O_", "");
          if (prop == "parent") {
            el = $G("parent");
            if (ds[prop] == "") {
              el.addClass("valid");
              el.removeClass("invalid");
            } else {
              el.addClass("invalid");
              el.removeClass("valid");
            }
          } else if (id > 0 && q == id) {
            if (option) {
              option.selected = "selected";
            }
          } else if (t > 0) {
            option = document.createElement("option");
            option.value = q;
            option.innerHTML = ds[prop];
            sel.appendChild(option);
          }
        }
      } else if (xhr.responseText != "") {
        alert(xhr.responseText);
      }
    });
  };
  var menuAction = function() {
    var c = $E("action").value;
    forEach($E("menu_action").getElementsByTagName("div"), function() {
      if ($G(this).hasClass("action")) {
        if ($G(this).hasClass(c)) {
          this.removeClass("hidden");
        } else {
          this.addClass("hidden");
        }
      }
    });
  };
  var doCopy = function() {
    var lng = $E("language").value;
    var id = $E("id").value.toInt();
    if (id > 0 && lng !== "") {
      send(
        "index.php/index/model/menuwrite/action",
        "action=copy&id=" + id + "&lng=" + lng,
        doFormSubmit
      );
    }
  };
  $G("copy_menu").addEvent("click", doCopy);
  $G("action").addEvent("change", menuAction);
  $G("parent").addEvent("change", getMenus);
  $G("type").addEvent("change", getMenus);
  getMenus.call(this);
  menuAction();
}
var dataTableActionCallback = function(xhr) {
  var el,
    val,
    toplv = -1,
    ds = xhr.responseText.toJSON();
  if (ds) {
    if (ds.modal) {
      modal = new GModal().show(ds.modal);
      ds.modal.evalScript();
    } else {
      for (prop in ds) {
        val = ds[prop];
        if (prop == "location") {
          if (val == "reload") {
            if (loader) {
              loader.reload();
            } else {
              window.location.reload();
            }
          } else {
            window.location.href = val;
          }
        } else if (prop == "delete_id") {
          $G(val).remove();
        } else if (prop == "alert") {
          alert(val);
        } else if (prop == "elem") {
          el = $E(val);
          if (el) {
            el.className = ds.class;
            if (ds.title) {
              el.title = ds.title;
            }
          }
        } else if ($E(prop)) {
          var el = $E(prop),
            tag = el.tagName.toLowerCase();
          if (tag == "th" && /r[0-9]+/.test(prop)) {
            var as = val.split("|");
            el.innerHTML = as[0];
            $E("move_left_" + as[2]).className =
              as[1] == 0 ? "hidden" : "icon-move_left";
            $E("move_right_" + as[2]).className =
              as[1] > toplv ? "hidden" : "icon-move_right";
            toplv = as[1];
          } else {
            $G(prop).setValue(decodeURIComponent(val).replace(/\%/g, "&#37;"));
          }
        }
      }
    }
  } else if (xhr.responseText != "") {
    alert(xhr.responseText);
  }
};

function doChangeLanguage(btn, url) {
  var doClick = function() {
    window.location = url + "&language=" + $E("language").value;
  };
  callClick(btn, doClick);
}

function checkIndexModule() {
  var value = this.value;
  var patt = /^[a-z0-9]{1,}$/;
  if (!patt.test(value)) {
    this.invalid(this.title);
  } else {
    return (
      "action=module&value=" +
      encodeURIComponent(value) +
      "&id=" +
      $E("id").value +
      "&lng=" +
      $E("language").value +
      "&owner=" +
      $E("owner").value
    );
  }
}

function checkIndexTopic() {
  var value = this.value;
  if (value.length < 3) {
    this.invalid(this.title);
  } else {
    return (
      "action=topic&value=" +
      encodeURIComponent(value) +
      "&id=" +
      $E("id").value +
      "&lng=" +
      $E("language").value
    );
  }
}
var indexPreview = function() {
  var id = $E("id").value.toInt();
  if (id > 0) {
    window.open(
      WEB_URL + "index.php?module=" + $E("owner").value + "&id=" + id,
      "preview"
    );
  }
};
var doIndexCopy = function() {
  var lng = $E("language").value;
  var id = $E("id").value.toInt();
  if (id > 0 && lng !== "") {
    send(
      "index.php/index/model/pagewrite/copy",
      "id=" + id + "&lng=" + lng + "&action=" + this.id,
      doFormSubmit
    );
  }
};

function initIndexWrite() {
  var module = new GValidator(
    "module",
    "keyup,change",
    checkIndexModule,
    "index.php/index/model/checker/module",
    null,
    "setup_frm"
  );
  var topic = new GValidator(
    "topic",
    "keyup,change",
    checkIndexTopic,
    "index.php/index/model/checker/topic",
    null,
    "setup_frm"
  );
  $G("language").addEvent("change", function() {
    if (topic.input.value != "") {
      topic.validate();
    }
    if (module.input.value != "") {
      module.validate();
    }
  });
  callClick("btn_copy", doIndexCopy);
  callClick("btn_preview", indexPreview);
}

function initFirstRowNumberOnly(tr, row) {
  forEach($G(tr).elems("input"), function(item, index) {
    if (index == 0) {
      $G(item).addEvent("keypress", numberOnly);
    }
  });
}

function initCopyToClipboard(id) {
  forEach($E(id).querySelectorAll('.icon-copy'), function() {
    callClick(this, function() {
      copyToClipboard(this.title);
      document.body.msgBox(trans("successfully copied to clipboard"));
      return false;
    });
  });
}

function showDebug() {
  var t = 0;
  var _get = function() {
    return "action=get&t=" + t;
  };
  var req = new GAjax().autoupdate(
    "index.php/index/model/debug/action",
    5,
    _get,
    function(xhr) {
      var content = $E("debug_layer");
      if (content) {
        forEach(xhr.responseText.split("\n"), function() {
          var line = this.split("\t");
          if (line.length == 3) {
            t = line[0];
            var div = document.createElement("div");
            var time = document.createElement("time");
            time.innerHTML = "<b>" + line[1] + "</b> : " + t;
            div.appendChild(time);
            var p = document.createElement("p");
            p.innerHTML = line[2];
            div.appendChild(p);
            content.appendChild(div);
            content.scrollTop = content.scrollHeight;
          }
        });
      } else {
        req.abort();
      }
    }
  );
  $G("debug_clear").addEvent("click", function() {
    if (confirm(trans("You want to XXX ?").replace(/XXX/, trans("delete")))) {
      send("index.php/index/model/debug/action", "action=clear", function(xhr) {
        $E("debug_layer").innerHTML = xhr.responseText;
      });
    }
  });
}
var confirmAction = function(msg, action, id, mid) {
  if (
    action == "published" ||
    action == "can_reply" ||
    action == "move_left" ||
    action == "move_right"
  ) {
    return "action=" + action + "&id=" + id + (mid ? "&mid=" + mid : "");
  } else if (
    confirm(trans("You want to XXX the selected items ?").replace(/XXX/, msg))
  ) {
    return "action=" + action + "&id=" + id + (mid ? "&mid=" + mid : "");
  }
  return "";
};

function checkSaved(button, url, write_id, target) {
  callClick(button, function() {
    var id = floatval($E(write_id).value);
    if (id == 0) {
      alert(trans("Please save before continuing"));
    } else if (target == "_self") {
      window.location = url.replace("&amp;", "&") + "&id=" + id;
    } else {
      window.open(url.replace("&amp;", "&") + "&id=" + id);
    }
  });
}

function getNews(div) {
  send("index.php/index/model/getnews/get", null, function(xhr) {
    if ($E(div)) {
      $E(div).innerHTML = xhr.responseText;
    }
  });
}

function getUpdate(v) {
  send("index.php/index/model/getupdate/get", "v=" + v, function(xhr) {
    if (xhr.responseText != "" && !/Not\sFound/.test(xhr.responseText)) {
      document.body.msgBox(xhr.responseText, "message");
    }
  });
}

function callInstall(c) {
  callClick("install_btn", function() {
    send("index.php/index/controller/installing", "module=" + c, function(xhr) {
      ds = xhr.responseText.toJSON();
      if (ds) {
        $E("install").innerHTML = ds.content;
        if (ds.location) {
          window.setTimeout(function() {
            window.location = ds.location;
          }, 5000);
        }
      } else if (xhr.responseText != "") {
        $E("install").innerHTML = xhr.responseText;
      }
    });
  });
}

function initLinesettings() {
  callClick('line_api_key_test', function() {
    send("index.php/index/model/meta/linetest", null);
  });
  var doCopy = function() {
    copyToClipboard(this.value);
    document.body.msgBox(trans('successfully copied to clipboard'));
    return false;
  };
  callClick('line_callback_url', doCopy);
  callClick('line_webhook_url', doCopy);
}

function findUser(name, id, from, count) {
  var SearchGet = function() {
    var value = $E(name).value;
    if (value != "") {
      q = "name=" + encodeURIComponent(value);
      q += "&from=" + (from || "name,email");
      q += "&count=" + (count || 10);
      return q;
    }
    return null;
  };

  function SearchCallback() {
    $E(name).value = this.name.unentityify();
    $E(id).value = this.id;
    $G(name).replaceClass("invalid", "valid");
  }

  function SearchPopulate() {
    return (
      '<p><span class="icon-user">' + this.name.unentityify() + "</span></p>"
    );
  }

  function SearchRequest() {
    $G(name).reset();
    $E(id).value = 0;
  }

  function SearchChanged() {
    $G(name).invalid();
    $E(id).value = 0;
  }
  new GAutoComplete(name, {
    className: "gautocomplete",
    get: SearchGet,
    url: "index.php/index/model/autocomplete/findUser",
    callBack: SearchCallback,
    populate: SearchPopulate,
    onRequest: SearchRequest,
    onChanged: SearchChanged
  });
}
