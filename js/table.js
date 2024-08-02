/**
 * GTable
 * Javascript data table for Kotchasan Framework
 *
 * @filesource js/table.js
 * @link https://www.kotchasan.com/
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 */
(function() {
  "use strict";
  window.GTable = GClass.create();
  GTable.prototype = {
    initialize: function(id, o) {
      this.options = {
        ajax: null,
        cols: [],
        headers: [],
        action: null,
        actionCallback: null,
        actionConfirm: null,
        onBeforeDelete: null,
        onDelete: null,
        onAddRow: null,
        onInitRow: null,
        onChanged: null,
        pmButton: false,
        dragColumn: -1,
        checkCol: -1,
        primaryKey: 'id',
        maxLinks: 9,
        emptyTableText: 'No data available in this table.'
      };
      for (var prop in o) {
        if (prop == "debug" && o.debug != "") {
          console.log(o.debug);
        } else {
          this.options[prop] = o[prop];
        }
      }
      this.table = $E(id);
      this.search = o["search"] || "";
      this.sort = o["sort"] || null;
      this.page = o["page"] || 1;
      this.sort_patt = /sort_(none|asc|desc)\s(col_([\w]+))(|\s.*)$/;
      this.submit = null;
      if (this.options.onAddRow) {
        this.options.onAddRow = window[this.options.onAddRow];
        if (!Object.isFunction(this.options.onAddRow)) {
          this.options.onAddRow = null;
        }
      }
      if (this.options.onBeforeDelete) {
        this.options.onBeforeDelete = window[this.options.onBeforeDelete];
        if (!Object.isFunction(this.options.onBeforeDelete)) {
          this.options.onBeforeDelete = null;
        }
      }
      if (this.options.onDelete) {
        this.options.onDelete = window[this.options.onDelete];
        if (!Object.isFunction(this.options.onDelete)) {
          this.options.onDelete = null;
        }
      }
      if (this.options.onInitRow) {
        this.options.onInitRow = window[this.options.onInitRow];
        if (!Object.isFunction(this.options.onInitRow)) {
          this.options.onInitRow = null;
        }
      }
      if (this.options.onChanged) {
        this.options.onChanged = window[this.options.onChanged];
        if (!Object.isFunction(this.options.onChanged)) {
          this.options.onChanged = null;
        }
      }

      var hs,
        action_patt = /button[\s][a-z]+[\s]action/,
        temp = this;

      var _doSort = function(e) {
        if ((hs = temp.sort_patt.exec(this.className))) {
          var sort = [];
          if (GEvent.isCtrlKey(e)) {
            var patt = new RegExp(hs[3] + "[\\s](asc|desc|none)");
            if (temp.sort) {
              forEach(temp.sort.split(","), function() {
                if (!patt.test(this)) {
                  sort.push(this);
                }
              });
            }
          } else {
            forEach($G(temp.table).elems("th"), function() {
              var ds = temp.sort_patt.exec(this.className);
              if (ds) {
                this.className = 'sort_none col_' + ds[3] + (ds[4] ? ds[4] : '');
              }
            });
          }
          if (hs[1] == "none") {
            this.className = 'sort_asc col_' + hs[3] + (hs[4] ? hs[4] : '');
            sort.push(hs[3] + "%20asc");
          } else if (hs[1] == "asc") {
            this.className = 'sort_desc col_' + hs[3] + (hs[4] ? hs[4] : '');
            sort.push(hs[3] + "%20desc");
          } else {
            this.className = 'sort_none col_' + hs[3] + (hs[4] ? hs[4] : '');
            sort.push(hs[3] + "%20none");
          }
          if (temp.options.ajax) {
            temp.callAjax();
          } else {
            temp.sort = sort.join(",");
            window.location = temp.redirect();
          }
        }
      };
      var doAction = function() {
        var action = "",
          cs = temp.getCheck();
        if (cs.length == 0) {
          alert(trans("Please select at least one item").replace(/XXX/, trans('Checkbox')));
        } else {
          cs = cs.join(",");
          var t,
            f = this.get("for"),
            fn = window[temp.options.actionConfirm];
          if ($E(f).type.toLowerCase() == 'text') {
            t = this.innerText;
          } else {
            t = $G(f).getText();
          }
          t = t ? t.strip_tags() : null;
          if (Object.isFunction(fn)) {
            action = fn(t, $E(f).value, cs);
          } else {
            if (confirm(trans("You want to XXX the selected items ?").replace(/XXX/, t))) {
              action = "module=" + f + "&action=" + $E(f).value + "&id=" + cs;
            }
          }
          if (action != "") {
            temp.callAction(this, action);
          }
        }
      };
      var doSearchChanged = function() {
        if (temp.input_search.value == "") {
          temp.input_search.parentNode.parentNode.className = 'search';
        } else {
          temp.input_search.parentNode.parentNode.className = 'search with_text';
        }
      };
      if (this.table) {
        if (this.options.ajax) {
          this.callAjax();
        }
        forEach($G(this.table).elems("th"), function() {
          if (temp.sort_patt.test(this.className)) {
            callClick(this, _doSort);
          }
        });
        this.initTABLE();
        forEach(this.table.elems("label"), function() {
          if (action_patt.test(this.className)) {
            callClick(this, doAction);
          }
        });
        if (this.input_search) {
          $G(this.input_search).addEvent("change", doSearchChanged);
          doSearchChanged.call(this);
        }
        if (typeof loader !== "undefined") {
          forEach(this.table.querySelectorAll("form.table_nav"), function() {
            this.onsubmit = function() {
              var urls = this.action.split("?"),
                obj = new Object();
              if (urls[1]) {
                forEach(urls[1].split("&"), function() {
                  var hs = this.split("=");
                  if (hs.length == 2 && hs[1] != "") {
                    obj[hs[0]] = hs[1];
                  }
                });
                forEach(this.querySelectorAll("input,select"), function() {
                  obj[this.name] = this.value;
                });
                var q = new Array();
                for (var prop in obj) {
                  if (prop == "search") {
                    q.push(prop + "=" + encodeURIComponent(obj[prop]));
                  } else if (prop != "" && prop != "time") {
                    q.push(prop + "=" + obj[prop]);
                  }
                }
                q.push("time=" + new Date().getTime());
                loader.setParams(q.join("&"));
              }
              return false;
            };
          });
        }
      }
    },
    showCaption: function(message) {
      const captions = this.table.getElementsByTagName('caption');
      if (captions.length > 0) {
        captions[0].innerHTML = message;
      }
    },
    callAjax: function(page) {
      var filterObj = {},
        sortObj = {},
        params = [],
        sort = [],
        hs,
        tbody = this.getTBODY(),
        form = $E(this.table).getElementsByTagName('form')[0],
        formData = new FormData(form),
        temp = this;
      formData.forEach(function(value, key) {
        if (key != 'sort') {
          filterObj[key] = encodeURIComponent(value);
        }
      });
      forEach($G(this.table).elems("th"), function() {
        hs = temp.sort_patt.exec(this.className);
        if (hs) {
          if (hs[1] == "asc" || hs[1] == "desc") {
            sortObj[hs[3]] = hs[1];
          }
        }
      });
      for (const key in sortObj) {
        sort.push(key + ' ' + sortObj[key]);
      }
      if (sort.length > 0) {
        let value = sort.join(','),
          elem = form.elements['sort'];
        if (elem) {
          elem.value = value;
        }
        filterObj['sort'] = encodeURIComponent(value);
      }
      for (const key in filterObj) {
        params.push(key + '=' + filterObj[key]);
      }
      if (page && page > 0) {
        params.push('page=' + page);
      }
      this.msgRow(tbody, '&nbsp;', 'wait');
      send(this.options.ajax, params.join('&'), function(xhr) {
        console.log(xhr.responseText);
        tbody.innerHTML = '';
        let headers = temp.options.headers,
          cols = temp.options.cols,
          primaryKey = temp.options.primaryKey,
          tableId = temp.table.id,
          ds = xhr.responseText.toJSON();
        if (ds) {
          if (ds.datas && ds.datas.length > 0) {
            for (const index in ds.datas) {
              let td,
                header,
                data = ds.datas[index],
                id = typeof data[primaryKey] == 'undefined' ? null : data[primaryKey],
                tr = document.createElement('tr');
              tr.id = tableId + '_' + id;
              if (temp.options.dragColumn > -1) {
                tr.className = 'sort';
              }
              for (const col in headers) {
                header = headers[col];
                if (col == temp.options.checkCol) {
                  td = document.createElement('td');
                  td.className = 'check-column';
                  let a = document.createElement('a');
                  a.className = 'icon-uncheck';
                  if (id != null) {
                    a.id = 'check_' + id;
                  }
                  td.appendChild(a);
                  tr.appendChild(td);
                } else if (col == temp.options.dragColumn) {
                  td = document.createElement('td');
                  td.className = 'center';
                  let a = document.createElement('a');
                  a.className = 'icon-move';
                  if (id != null) {
                    a.id = 'move_' + id;
                  }
                  td.title = trans('Drag and drop to reorder');
                  td.appendChild(a);
                  tr.appendChild(td);
                }
                td = document.createElement('td');
                if (typeof data[header] != 'undefined') {
                  if (cols[header] && cols[header]['class']) {
                    td.className = cols[header]['class'];
                  }
                  td.innerHTML = data[header];
                }
                tr.appendChild(td);
              }
              if (temp.options.pmButton) {
                td = document.createElement('td');
                td.className = 'icons';
                let div = document.createElement('div');
                td.appendChild(div);
                let a = document.createElement('a');
                a.className = 'icon-plus';
                a.title = trans('Add');
                div.appendChild(a);
                a = document.createElement('a');
                a.className = 'icon-minus';
                a.title = trans('Delete');
                div.appendChild(a);
                tr.appendChild(td);
              }
              tbody.appendChild(tr);
            }
            temp.generateSplitpage(ds.totalPage, ds.page);
            temp.initTABLE();
          } else if (ds.error) {
            temp.msgRow(tbody, ds.error, 'error-table');
          } else {
            temp.msgRow(tbody, temp.options.emptyTableText, 'empty-table');
          }
          if (temp.options.onChanged) {
            temp.options.onChanged.call(temp, tbody, ds.datas);
          }
          if (ds.caption) {
            temp.showCaption(ds.caption);
          }
        } else if (xhr.responseText != '') {
          console.log(xhr.responseText);
        }
      });
    },
    generateSplitpage: function(totalPage, page) {
      let temp = this,
        elem,
        start = 1,
        maxLinks = this.options.maxLinks,
        splitpage = this.table.querySelector('.splitpage');
      splitpage.innerHTML = '';

      if (totalPage > maxLinks) {
        start = page - Math.floor(maxLinks / 2);
        if (start < 1) {
          start = 1;
        } else if (start + maxLinks > totalPage) {
          start = totalPage - maxLinks + 1;
        }
      }
      let end = start + maxLinks - 1;
      for (let i = start; i <= totalPage && maxLinks > 0; i++) {
        if (i == page) {
          elem = document.createElement('strong');
          elem.title = trans('showing page') + ' ' + i;
          elem.innerHTML = i;
        } else {
          elem = document.createElement('a');
          if (i == start) {
            elem.innerHTML = 1;
            elem.title = trans('go to page') + ' ' + 1;
          } else if (i == end) {
            elem.innerHTML = totalPage;
            elem.title = trans('go to page') + ' ' + totalPage;
          } else {
            elem.innerHTML = i;
            elem.title = trans('go to page') + ' ' + i;
          }
          elem.onclick = function(e) {
            temp.callAjax(this.innerHTML);
          };
        }

        splitpage.appendChild(elem);
        maxLinks--;
      }
    },
    msgRow: function(tbody, message, className) {
      const tr = document.createElement('tr'),
        td = document.createElement('td');
      td.innerHTML = message;
      td.className = className;
      td.colSpan = this.options.headers.length;
      tr.appendChild(td);
      tbody.appendChild(tr);
    },
    initTABLE: function() {
      var temp = this;

      this.initTR(this.table);

      forEach(this.table.elems("tbody"), function() {
        temp.initTBODY(this, null);
      });

      if (this.options.dragColumn > -1) {
        new GDragDrop(this.table, {
          dragClass: "icon-move",
          endDrag: function() {
            var trs = new Array();
            forEach(temp.table.elems("tr"), function() {
              if (this.id) {
                trs.push(this.id.replace(temp.table.id + "_", ""));
              }
            });
            if (trs.length > 1) {
              temp.callAction(this, "action=move&data=" + trs.join(","));
            }
          }
        });
      }
      forEach(this.table.elems("button"), function() {
        if (this.className == "clear_search") {
          temp.clear_search = this;
          temp.input_search = this.parentNode.firstChild.firstChild;
          callClick(this, function() {
            temp.input_search.value = "";
            if (temp.submit) {
              temp.submit.click();
            }
          });
        } else if (this.type == "submit") {
          temp.submit = this;
        } else if (this.id != "") {
          callClick(this, function() {
            temp._doButton(this);
          });
        }
      });
      if (this.options.action) {
        window.setTimeout(function() {
          if ($E(temp.table)) {
            forEach(temp.table.elems("tbody"), function() {
              forEach(
                this.querySelectorAll("select,input,textarea"),
                function() {
                  if (this.id != "") {
                    $G(this).addEvent("change", function() {
                      temp._doButton(this);
                    });
                  }
                }
              );
            });
          }
        }, 1000);
      }
    },
    getCheck: function() {
      var cs = new Array(),
        chk = /check_[0-9]+/;
      forEach(this.table.elems("a"), function() {
        if (chk.test(this.id) && $G(this).hasClass("icon-check")) {
          cs.push(this.id.replace("check_", ""));
        }
      });
      return cs;
    },
    callAction: function(el, action) {
      var hs = this.options.action.split("?");
      if (hs[1]) {
        action = hs[1] + "&" + action;
      }
      action += "&src=" + this.table.id;
      if (el.value) {
        action += "&value=" + encodeURIComponent(el.value);
      }
      var temp = this;
      el.addClass("wait");
      send(hs[0], action, function(xhr) {
        el.removeClass("wait");
        if (temp.options.actionCallback) {
          var fn = window[temp.options.actionCallback];
          if (Object.isFunction(fn)) {
            fn(xhr);
          }
        } else if (xhr.responseText != "") {
          alert(xhr.responseText);
        } else {
          window.location.reload();
        }
      });
    },
    _doButton: function(input) {
      var action = "",
        cs = [],
        patt = /^([a-z0-9_\-]+)_([0-9]+)(_([0-9]+))?$/,
        q = input.get("data-confirm"),
        chk = input.get("data-checkbox");
      if (chk) {
        cs = this.getCheck();
        if (cs.length == 0) {
          alert(trans("Please select at least one item").replace(/XXX/, trans('Checkbox')));
          return;
        }
      }
      if (this.options.actionConfirm) {
        var fn = window[this.options.actionConfirm],
          hs = patt.exec(input.id);
        if (hs && Object.isFunction(fn)) {
          var t = input.getText();
          t = t ? t.strip_tags() : null;
          action = fn(t, hs[1], hs[2], hs[4]);
        } else {
          action = "action=" + input.id;
        }
      } else if (!q || confirm(q)) {
        hs = patt.exec(input.id);
        if (hs) {
          if (hs[1] == "delete" || hs[1] == "cancel") {
            if (cs.length > 0 && confirm(trans("You want to XXX the selected items ?").replace(/XXX/, trans(hs[1])))) {
              action = "action=" + hs[1] + "&id=" + hs[2] + (hs[4] ? '&opt=' + hs[4] : '');
            } else if (confirm(trans("You want to XXX ?").replace(/XXX/, trans(hs[1])))) {
              action = "action=" + hs[1] + "&id=" + hs[2] + (hs[4] ? '&opt=' + hs[4] : '');
            }
          } else if (hs[4]) {
            action = "action=" + hs[1] + "_" + hs[2] + "&id=" + hs[4];
          } else {
            action = "action=" + hs[1] + "&id=" + hs[2];
          }
        } else {
          action = "action=" + input.id;
        }
      }
      if (action != "") {
        if (cs.length > 0) {
          action += '&ids=' + cs.join(',');
        }
        this.callAction(input, action);
      }
    },
    getTBODY: function() {
      let tbody,
        tbodies = this.table.getElementsByTagName('tbody');
      if (tbodies.length == 0) {
        tbody = document.createElement('tbody');
        this.table.getElementsByTagName('table')[0].appendChild(tbody);
      } else {
        tbody = tbodies[0];
        tbody.innerHTML = '';
      }
      return tbody;
    },
    initTBODY: function(tbody, tr) {
      var row = 0,
        temp = this;
      forEach($G(tbody).elems("tr"), function() {
        if (temp.options.pmButton) {
          this.id = temp.table.id + "_" + row;
          forEach(this.querySelectorAll("select,input,textarea"), function() {
            this.id = this.name.replace(/([\[\]_]+)/g, "_") + row;
          });
        }
        if (tr === null || tr === this) {
          if (temp.options.onInitRow) {
            temp.options.onInitRow.call(temp, this, row);
          }
          if (temp.options.action) {
            var move = /(check|move)_([0-9]+)/;
            forEach($G(this).elems("a"), function() {
              var id = this.id;
              if (id && !move.test(id)) {
                callClick(this, function() {
                  temp._doButton(this);
                });
              }
            });
          }
        }
        row++;
      });
      let menus = this.table.querySelectorAll('.menubutton > ul'),
        tablebody = this.table.querySelector('.tablebody'),
        table_height = $G(tablebody).getHeight(),
        vp = $G(tablebody).viewportOffset(),
        height = 0;
      forEach(menus, function() {
        height = Math.max(height, $G(this).getHeight());
      });
      forEach(menus, function() {
        if (this.getTop() - vp.top + height > table_height) {
          $G(this.parentNode).addClass('uppermenu');
        }
      });
      tablebody.style.paddingTop = height + 'px';
      tablebody.style.marginTop = '-' + height + 'px';
    },
    initTR: function(el) {
      var hs,
        a_patt = /(delete|icon)[_\-](plus|minus|[0-9]+)/,
        check_patt = /check_([0-9]+)/,
        temp = this;
      var aClick = function() {
        var c = this.className;
        if (c == "icon-plus") {
          var tr = $G(this.parentNode.parentNode.parentNode);
          var tbody = tr.parentNode;
          var ntr = tr.copy(false);
          tr.after(ntr);
          temp.initTR(ntr);
          if (temp.options.onAddRow) {
            ret = temp.options.onAddRow.call(temp, ntr);
          }
          temp.initTBODY(tbody, ntr);
          ntr.highlight();
          ntr = ntr.elems("input")[0];
          if (ntr) {
            ntr.focus();
            ntr.select();
          }
        } else if (c == "icon-minus") {
          var tr = $G(this.parentNode.parentNode.parentNode);
          var tbody = $G(tr.parentNode);
          var ret = true;
          if (temp.options.onBeforeDelete) {
            ret = temp.options.onBeforeDelete.call(temp, tr);
          }
          if (ret) {
            if (tbody.elems("tr").length > 1) {
              tr.remove();
              temp.initTBODY(tbody, false);
              if (temp.options.onDelete) {
                temp.options.onDelete.call(temp);
              }
            }
          }
        } else if ((hs = a_patt.exec(c))) {
          var action = "";
          if (hs[1] == "delete" && confirm(trans("You want to XXX ?").replace(/XXX/, trans("delete")))) {
            action = "action=delete&id=" + hs[2];
          }
          if (action != "" && temp.options.action) {
            send(temp.options.action, action, function(xhr) {
              var ds = xhr.responseText.toJSON();
              if (ds) {
                if (ds.alert && ds.alert != "") {
                  alert(ds.alert);
                } else if (ds.action) {
                  if (ds.action == "delete") {
                    var tr = $G(temp.table.id + "_" + ds.id);
                    var tbody = tr.parentNode;
                    tr.remove();
                    temp.initTBODY(tbody, tr);
                  }
                }
              } else if (xhr.responseText != "") {
                alert(xhr.responseText);
              }
            }, this);
          }
        }
      };
      var saClick = function() {
        this.focus();
        var chk = this.hasClass("icon-check");
        forEach(el.elems("a"), function() {
          if (check_patt.test(this.id)) {
            this.className = chk ? "icon-uncheck" : "icon-check";
            this.title = chk ? trans("check") : trans("uncheck");
          } else if ($G(this).hasClass("checkall")) {
            this.className = chk ? "checkall icon-uncheck" : "checkall icon-check";
            this.title = chk ? trans("select all") : trans("select none");
          }
        });
        return false;
      };
      var sClick = function() {
        this.focus();
        var chk = $G(this).hasClass("icon-check");
        this.className = chk ? "icon-uncheck" : "icon-check";
        this.title = chk ? trans("check") : trans("uncheck");
        forEach(el.elems("a"), function() {
          if (this.hasClass("checkall")) {
            this.className = "checkall icon-uncheck";
            this.title = trans("select all");
          }
        });
        return false;
      };
      forEach($G(el).elems("a"), function() {
        if (a_patt.test(this.className)) {
          callClick(this, aClick);
        } else if ($G(this).hasClass("checkall")) {
          this.title = trans("select all");
          callClick(this, saClick);
        } else if (check_patt.test(this.id)) {
          this.title = trans("check");
          callClick(this, sClick);
        }
      });
      forEach(el.querySelectorAll('.icon-copy'), function() {
        callClick(this, function() {
          if (this.value) {
            copyToClipboard(this.value);
          } else if (this.title) {
            copyToClipboard(this.title);
          } else if (this.innerHTML) {
            copyToClipboard(this.innerHTML.strip_tags());
          } else {
            return false;
          }
          document.body.msgBox(trans('successfully copied to clipboard'));
          return false;
        });
      });
    },
    setSort: function(sort, patt) {
      var hs;
      forEach(this.table.elems("th"), function() {
        hs = patt.exec(this.className);
        if (hs) {
          if (sort == hs[2]) {
            this.className = this.className.replace("sort_" + hs[1], "sort_" + (hs[1] == "asc" ? "desc" : "asc"));
          } else {
            this.className = this.className.replace("sort_" + hs[1], "sort_none");
          }
        }
      });
    },
    redirect: function() {
      var hs,
        patt = /^(.*)=(.*)$/,
        urls = {},
        u = window.location.href,
        us2 = u.split("#"),
        us1 = us2[0].split("?");
      forEach([us1[1], us2[1]], function() {
        if (this) {
          forEach(this.split("&"), function() {
            if ((hs = patt.exec(this))) {
              hs[1] = hs[1].toLowerCase();
              hs[2] = hs[2].toLowerCase();
              if (hs[1] != "page" && hs[1] != "sort" && hs[1] != "search" && !(hs[1] == "action" && (hs[2] == "login" || hs[2] == "logout"))) {
                urls[hs[1]] = this;
              }
            } else {
              urls[this] = this;
            }
          });
        }
      });

      var us = Object.toArray(urls);
      us.push("page=" + this.page);
      if (this.sort) {
        us.push("sort=" + this.sort);
      }
      if (this.search) {
        us.push("search=" + encodeURIComponent(this.search));
      }
      if (us2.length == 2) {
        u = us2[0];
        if (us.length > 0) {
          u += "#" + us.join("&");
        }
      } else {
        u = us1[0];
        if (us.length > 0) {
          u += "?" + us.join("&");
        }
      }
      return u;
    }
  };
})();
