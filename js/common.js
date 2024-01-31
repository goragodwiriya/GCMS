/**
 * Javascript Library for Ajax Front-end and Back-end
 *
 * @filesource js/common.js
 * @link https://www.kotchasan.com/
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 */
var mtooltip,
  modal = null,
  loader = null,
  editor = null,
  G_Lightbox = null;

function mTooltipShow(id, action, method, elem) {
  if (Object.isNull(mtooltip)) {
    mtooltip = new GTooltip({
      className: 'member-tooltip',
      fade: true,
      cache: true
    });
  }
  mtooltip.showAjax(elem, action, method + '&id=' + id, function(xhr) {
    if (loader) {
      loader.init(this.tooltip);
    }
  });
}

function send(target, query, callback, wait, c) {
  var req = new GAjax();
  req.initLoading(wait || 'wait', false, c);
  req.send(target, query, function(xhr) {
    if (callback) {
      callback.call(this, xhr);
    }
  });
}
var hideModal = function() {
  if (modal != null) {
    modal.hide();
  }
};

function showModal(src, qstr, doClose, className, button) {
  send(src, qstr, function(xhr) {
    var ds = xhr.responseText.toJSON();
    var detail = '';
    if (ds) {
      if (ds.alert) {
        alert(ds.alert);
      } else if (ds.detail) {
        detail = decodeURIComponent(ds.detail);
      } else if (ds.modal) {
        detail = ds.modal;
      }
    } else {
      detail = xhr.responseText;
    }
    if (detail != '') {
      modal = new GModal({
        onclose: doClose,
        parent: button
      }).show(detail, className);
      detail.evalScript();
    }
  });
}

function defaultSubmit(ds) {
  var _alert = '',
    _input = false,
    _url = false,
    _location = false,
    _eval = false,
    t,
    el,
    remove = /remove([0-9]{0,})/;

  function unentityify(text) {
    return text.unentityify()
      .replace(/&nbsp;/g, ' ')
      .strip_tags()
      .trim();
  }

  for (var prop in ds) {
    var val = ds[prop];
    if (prop == 'error') {
      _alert = trans(val);
    } else if (prop == 'debug') {
      console.log(val);
    } else if (prop == 'alert') {
      _alert = val;
    } else if (prop == 'message') {
      document.body.msgBox(trans(val));
    } else if (prop == 'warning') {
      document.body.msgBox(trans(val), 'warning');
    } else if (prop == 'tip') {
      document.body.msgBox(trans(val), 'tip', false);
    } else if (prop == 'checked') {
      if ($E(val)) {
        $E(val).checked = true;
      }
    } else if (prop == 'modal') {
      if (val == 'close') {
        if (modal) {
          modal.hide();
        }
      } else {
        if (!modal) {
          modal = new GModal();
        }
        modal.show(val);
        val.evalScript();
      }
    } else if (prop == 'elem') {
      el = $E(val);
      if (el) {
        if (ds.class) {
          el.className = ds.class;
        }
        if (ds.title) {
          el.title = ds.title;
        }
      }
    } else if (prop == 'location') {
      _location = val;
    } else if (prop == 'url') {
      _url = val;
      _location = val;
    } else if (prop == 'open') {
      window.setTimeout(function() {
        window.open(val.replace(/&amp;/g, '&'));
      }, 1);
    } else if (prop == 'tab') {
      initWriteTab('accordient_menu', val);
    } else if (prop == 'valid') {
      if ($E(val)) {
        $G(val).valid();
      }
    } else if (prop == 'eval') {
      _eval = val;
    } else if (remove.test(prop)) {
      if ($E(val)) {
        $G(val).fadeOut(function() {
          $G(val).remove();
        });
      }
    } else if ($E(prop)) {
      $G(prop).setValue(decodeURIComponent(val).replace(/\%/g, '&#37;'));
    } else if ($E(prop.replace('ret_', ''))) {
      el = $G(prop.replace('ret_', ''));
      if (el.display) {
        el = el.display;
      }
      if (val == '') {
        el.valid();
      } else {
        if (val == 'Please fill in' || val == 'Please select' || val == 'Please browse file' || val == 'already exist' || val == 'Please select at least one item' || val == 'Invalid data') {
          var label = el.findLabel();
          if (label) {
            t = unentityify(label.innerHTML);
          } else {
            if (typeof el.placeholder != 'undefined') {
              t = unentityify(el.placeholder);
            } else {
              t = '';
            }
            if (t == '') {
              t = unentityify(el.title);
            }
          }
          if (t != '') {
            if (val == 'already exist') {
              val = t + ' ' + trans(val);
            } else if (val == 'Please select at least one item') {
              val = PLEASE_SELECT_AT_LEAST_ONE_ITEM.replace('XXX', t)
            } else if (val == 'Invalid data') {
              val = INVALID_DATA.replace('XXX', t)
            } else {
              val = trans(val) + ' ' + t;
            }
          } else {
            val = trans(val);
          }
        } else if (val == 'this') {
          t = '';
          if (typeof el.title != 'undefined') {
            t = unentityify(el.title);
          }
          if (t == '' && typeof el.placeholder != 'undefined') {
            t = unentityify(el.placeholder);
          }
          val = t;
        }
        if (_input != el) {
          el.invalid(val);
        }
        if (_alert == '') {
          _alert = val;
          _input = el;
        }
      }
    }
  }
  if (_alert != '') {
    alert(_alert);
  }
  if (_input) {
    _input.focus();
    var tag = _input.tagName.toLowerCase();
    if (tag != 'select') {
      _input.highlight();
    }
    if (tag == 'input') {
      var type = _input.get('type').toLowerCase();
      if (type == 'text' || type == 'password') {
        _input.select();
      }
    }
  }
  if (_eval) {
    eval(_eval);
  }
  if (_location) {
    if (_location == 'reload') {
      if (loader) {
        loader.reload();
      } else {
        window.location.reload();
      }
    } else if (_location == 'refresh') {
      window.location.reload();
    } else if (_location == 'back') {
      if (loader) {
        loader.back();
      } else {
        window.history.go(-1);
      }
    } else if (loader && _location != _url) {
      loader.location(_location);
    } else {
      window.location = _location.replace(/&amp;/g, '&');
    }
  }
}

function doFormSubmit(xhr) {
  var datas = xhr.responseText.toJSON();
  if (datas) {
    defaultSubmit(datas);
  } else if (xhr.responseText != '') {
    console.log(xhr.responseText);
  }
}

function initWriteTab(id, sel) {
  function _doclick(sel) {
    forEach($E(id).getElementsByTagName('a'), function() {
      var a = this.id.replace('tab_', '');
      if ($E(a)) {
        this.className = a == sel ? 'select' : '';
        $E(a).style.display = a == sel ? 'block' : 'none';
      }
    });
    $E('tab').value = sel;
  }
  forEach($G(id).elems('a'), function() {
    if ($E(this.id.replace('tab_', ''))) {
      callClick(this, function() {
        _doclick(this.id.replace('tab_', ''));
        return false;
      });
    }
  });
  _doclick(sel);
}

function checkUsername() {
  var patt = /[a-zA-Z0-9@\.\-_]{6,}/,
    value = this.value,
    ids = this.id.split('_'),
    id = '&id=' + floatval($E(ids[0] + '_id').value);
  if (value == '') {
    this.invalid(this.title);
  } else if (patt.test(value)) {
    return 'value=' + encodeURIComponent(value) + id;
  } else {
    this.invalid(this.title);
  }
}

function checkEmail() {
  var value = this.value;
  var ids = this.id.split('_');
  var id = '&id=' + floatval($E(ids[0] + '_id').value);
  if (value == '') {
    this.invalid(this.title);
  } else if (/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/.test(value)) {
    return 'value=' + encodeURIComponent(value) + id;
  } else {
    this.invalid(this.title);
  }
}

function checkPhone() {
  var value = this.value;
  var ids = this.id.split('_');
  var id = '&id=' + floatval($E(ids[0] + '_id').value);
  if (value != '') {
    return 'value=' + encodeURIComponent(value) + id;
  }
}

function checkDisplayname() {
  var value = this.value;
  var ids = this.id.split('_');
  var id = '&id=' + floatval($E(ids[0] + '_id').value);
  if (value.length < 2) {
    this.invalid(this.title);
  } else {
    return 'value=' + encodeURIComponent(value) + '&id=' + id;
  }
}

function checkPassword() {
  var ids = this.id.split('_'),
    id = '&id=' + floatval($E(ids[0] + '_id').value),
    Password = $E(ids[0] + '_password'),
    Repassword = $E(ids[0] + '_repassword');
  if (Password.value == '' && Repassword.value == '') {
    if (id == 0) {
      this.Validator.invalid(this.Validator.title);
    } else {
      this.Validator.reset();
    }
    this.Validator.reset();
  } else if (Password.value == Repassword.value) {
    Password.Validator.valid();
    Repassword.Validator.valid();
  } else {
    this.Validator.invalid(this.Validator.title);
  }
}

function checkIdcard() {
  var value = this.value,
    ids = this.id.split('_'),
    id = '&id=' + floatval($E(ids[0] + '_id').value);
  if (value.length == 0) {
    this.reset();
  } else if (value.length < 9) {
    this.invalid(this.title);
  } else {
    return 'value=' + encodeURIComponent(value) + '&id=' + id;
  }
}

function checkAlias() {
  var value = this.value;
  if (value.length == 0) {
    this.reset();
  } else if (value.length < 3) {
    this.invalid(this.title);
  } else {
    return 'val=' + encodeURIComponent(value) + '&id=' + $E('id').value;
  }
}

function replaceURL(withParams, withoutParams, url) {
  var q,
    prop,
    withParams = withParams || {},
    withoutParams = withoutParams || {},
    new_url = new Object(),
    qs = Array();
  url = url || window.location.toString();
  var urls = url.replace(/\#/g, '&')
    .replace(/\?/g, '&')
    .split('&');
  var l = urls.length;
  if (l > 1) {
    for (var n = 1; n < l; n++) {
      if (urls[n] != 'action=login' && urls[n] != 'action=logout') {
        q = urls[n].split('=');
        if (q.length == 2 && !(withoutParams[q[0]] && (withoutParams[q[0]] == q[1] || withoutParams[q[0]] === null))) {
          new_url[q[0]] = q[1];
        }
      }
    }
  }
  for (prop in withParams) {
    new_url[prop] = withParams[prop];
  }
  for (prop in new_url) {
    if (new_url[prop] !== null) {
      qs.push(prop + '=' + new_url[prop]);
    } else {
      qs.push(prop);
    }
  }
  if (qs.length > 0) {
    return urls[0] + '?' + qs.join('&');
  } else {
    return urls[0];
  }
}

function getWebUri() {
  var port = floatval(window.location.port);
  var protocol = window.location.protocol;
  if ((protocol == 'http:' && port == 80) || (protocol == 'https:' && port == 443)) {
    port = '';
  } else {
    port = port > 0 ? ':' + port : '';
  }
  return protocol + '//' + window.location.hostname + port + '/';
}

function _doCheckKey(input, e, patt) {
  var val = input.value;
  var key = GEvent.keyCode(e);
  if (!((key > 36 && key < 41) || key == 8 || key == 9 || key == 13 || GEvent.isCtrlKey(e))) {
    val = String.fromCharCode(key);
    if (val !== '' && !patt.test(val)) {
      GEvent.stop(e);
      return false;
    }
  }
  return true;
}
var numberOnly = function(e) {
  return _doCheckKey(this, e, /[0-9]/);
};
var integerOnly = function(e) {
  return _doCheckKey(this, e, /[0-9\-]/);
};
var currencyOnly = function(e) {
  return _doCheckKey(this, e, /[0-9\.]/);
};

function setSelect(id, value) {
  forEach($E(id).getElementsByTagName('input'), function() {
    if (this.type.toLowerCase() == 'checkbox') {
      this.checked = value;
    }
  });
}

function selectChanged(src, action, callback) {
  $G(src).addEvent('change', function() {
    var temp = this;
    send(action, 'id=' + this.id + '&value=' + this.value, function(xhr) {
      if (xhr.responseText !== '') {
        callback.call(temp, xhr);
      }
    }, this);
  });
}
var doCustomConfirm = function(value) {
  return confirm(value);
};

function countryChanged(prefix) {
  var _contryChanged = function() {
    if (this.value != 'TH') {
      $G($E(prefix + '_provinceID').parentNode.parentNode).addClass('hidden');
      $G($E(prefix + '_province').parentNode.parentNode).removeClass('hidden');
    } else {
      $G($E(prefix + '_provinceID').parentNode.parentNode).removeClass('hidden');
      $G($E(prefix + '_province').parentNode.parentNode).addClass('hidden');
    }
  };
  if ($E(prefix + '_country')) {
    $G(prefix + '_country').addEvent('change', _contryChanged);
    _contryChanged.call($E(prefix + '_country'));
  }
}

function birthdayChanged(id, text) {
  $G(id).addEvent('change', function() {
    if (this.value) {
      var curr = new Date(this.value),
        age = new Date().compare(curr);
      this.text = text.replace('%s', curr.format('d M Y')).replace('%y', age.year).replace('%m', age.month).replace('%d', age.day);
    }
  });
}

function selectMenu(module) {
  forEach(document.querySelectorAll('#topmenu > ul > li'), function() {
    if ($G(this).hasClass(module)) {
      this.addClass('select');
    } else {
      this.removeClass('select');
    }
  });
  forEach(document.querySelectorAll('.sidemenu > ul > li'), function() {
    if ($G(this).hasClass(module)) {
      this.addClass('select');
    } else {
      this.removeClass('select');
    }
  });
}

function loadJavascript(id, src, onload) {
  var js,
    fjs = document.getElementsByTagName('head')[0];
  if (document.getElementById(id)) {
    return;
  }
  js = document.createElement('script');
  js.id = id;
  if (typeof onload == 'function') {
    js.onload = onload;
  }
  js.src = src;
  fjs.appendChild(js);
}

var doLogin = function() {
  showModal(WEB_URL + 'xhr.php', 'class=Index\\Member\\Controller&method=modal&action=' + this.get('data-action'), null, 'loginfrm');
  return false;
};

var doLoginSubmit = function(xhr) {
  var el,
    t,
    ds = xhr.responseText.toJSON();
  if (ds) {
    if (ds.alert && ds.alert != '') {
      if (ds.alert == 'Please fill in' && ds.input && $E(ds.input)) {
        el = $E(ds.input);
        if (el.placeholder) {
          t = el.placeholder.strip_tags();
        } else {
          t = el.title.strip_tags();
        }
        alert(trans(ds.alert) + ' ' + t);
      } else {
        alert(ds.alert);
      }
    }
    if (ds.action) {
      if (ds.action == 2 || ds.action == 'back') {
        if (loader) {
          loader.back();
        } else if (document.referrer) {
          window.location = document.referrer
            .toString()
            .replace('action=logout', '')
            .replace(/\?\&/, '?')
            .replace(/\?$/, '');
        } else {
          window.history.back();
        }
      } else if (ds.action == 1 || ds.action == 'reload') {
        if (loader) {
          loader.reload();
        } else {
          window.location = replaceURL({action: 'login'});
        }
      } else if (/^http.*/.test(ds.action)) {
        window.location = ds.action;
      }
    }
    if (ds.content && $E('login-box')) {
      hideModal();
      var content = decodeURIComponent(ds.content);
      var login = $G('login-box');
      login.setHTML(content);
      content.evalScript();
      if (loader) {
        loader.init(login);
      }
    }
    if (ds.input) {
      $G(ds.input)
        .invalid()
        .focus();
    }
  } else if (xhr.responseText != '') {
    console.log(xhr.responseText);
  }
};

function findUser(name, id, from, count) {
  var SearchGet = function() {
    var value = $E(name).value;
    if (value != '') {
      q = 'name=' + encodeURIComponent(value);
      q += '&from=' + (from || 'name,email');
      q += '&count=' + (count || 10);
      return q;
    }
    return null;
  };

  function SearchCallback() {
    $E(name).value = this.name.unentityify();
    $E(id).value = this.id;
    $G(name).replaceClass('invalid', 'valid');
  }

  function SearchPopulate() {
    return (
      '<p><span class="icon-user">' + this.name.unentityify() + '</span></p>'
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
    className: 'gautocomplete',
    get: SearchGet,
    url: 'index.php/index/model/autocomplete/findUser',
    callBack: SearchCallback,
    populate: SearchPopulate,
    onRequest: SearchRequest,
    onChanged: SearchChanged
  });
}

var createLikeButton;

function initWeb(module) {
  module = module ? module + '/' : '';
  if (navigator.userAgent.indexOf('MSIE') > -1) {
    document.body.addClass('ie');
  }
  forEach(document.body.elems('nav'), function() {
    if ($G(this).hasClass('topmenu sidemenu slidemenu gddmenu')) {
      new GDDMenu(this);
    }
  });
  var _scrolltop = 0;
  var toTop = 100;
  if ($E('toTop') && !$K.isMobile()) {
    if ($G('toTop').hasClass('fixed_top')) {
      document.addEvent('toTopChange', function() {
        if (document.body.hasClass('toTop')) {
          var _toTop = $G('toTop').copy();
          _toTop.zIndex = -1;
          _toTop.id = 'toTop_temp';
          _toTop.setStyle('opacity', 0);
          _toTop.removeClass('fixed_top');
          $G('toTop').after(_toTop);
        } else if ($E('toTop_temp')) {
          $G('toTop_temp').remove();
        }
      });
    }
    toTop = $E('toTop').getTop();
    document.addEvent('scroll', function() {
      var c = this.viewport.getscrollTop() > toTop;
      if (_scrolltop != c) {
        _scrolltop = c;
        if ($E('body')) {
          if (c) {
            $E('body').className = 'toTop';
          } else {
            $E('body').className = '';
          }
        } else {
          if (c) {
            document.body.addClass('toTop');
          } else {
            document.body.removeClass('toTop');
          }
        }
        document.callEvent('toTopChange');
      }
    });
  }
  var fontSize = floatval(Cookie.get(module + 'fontSize'));
  document.body.set('data-fontSize', floatval(document.body.getStyle('fontSize')));
  if (fontSize > 5) {
    document.body.setStyle('fontSize', fontSize + 'px');
  }
  forEach(document.body.elems('a'), function() {
    if (/^lang_([a-z]{2,2})$/.test(this.id)) {
      callClick(this, function(e) {
        var hs = /^lang_([a-z]{2,2})$/.exec(this.id);
        window.location = replaceURL({lang: hs[1]});
        GEvent.stop(e);
      });
    } else if (/font_size\s(small|normal|large)/.test(this.className)) {
      callClick(this, function(e) {
        fontSize = floatval(document.body.getStyle('fontSize'));
        var hs = /font_size\s(small|normal|large)/.exec(this.className);
        if (hs[1] == 'small') {
          fontSize = Math.max(6, fontSize - 2);
        } else if (hs[1] == 'large') {
          fontSize = Math.min(24, fontSize + 2);
        } else {
          fontSize = document.body.get('data-fontSize');
        }
        document.body.setStyle('fontSize', fontSize + 'px');
        Cookie.set(module + 'fontSize', fontSize);
        GEvent.stop(e);
      });
    }
  });
  if (use_ajax == 1 && $E('content')) {
    loader = new GLoader(
      WEB_URL + module + 'loader.php/index/controller/loader/index',
      function(xhr) {
        var scroll_to = 'scroll-to',
          content = $G('content'),
          datas = xhr.responseText.toJSON();
        document.body.onkeydown = null;
        if (datas) {
          for (var prop in datas) {
            var value = datas[prop];
            if (prop == 'detail') {
              content.setHTML(value);
              loader.init(content);
              content.replaceClass('loading', 'animation');
              content.Ready(function() {
                $K.init(content);
                value.evalScript();
              });
            } else if (prop == 'topic') {
              document.title = value.unentityify();
            } else if (prop == 'menu') {
              selectMenu(value);
            } else if (prop == 'to') {
              scroll_to = value;
            } else if ($E(prop)) {
              $E(prop).innerHTML = value;
            }
          }
          if (Object.isFunction(createLikeButton)) {
            createLikeButton();
          }
          if ($E(scroll_to)) {
            window.scrollTo(0, $G(scroll_to).getTop() - 10);
          }
        } else if (xhr.responseText != '') {
          console.log(xhr.responseText);
        }
      },
      null,
      function() {
        $G('content').replaceClass('animation', 'loading');
        return true;
      }
    );
    loader.initLoading('wait', false);
    loader.init(document);
  }
  $K.init(document.body);
  if (module == '') {
    new PDPA();
  }
}

if (navigator.userAgent.match(/(iPhone|iPod|iPad)/i)) {
  document.addEventListener('touchstart', function() {}, false);
}
