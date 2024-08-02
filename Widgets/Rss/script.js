/**
 GRSS and GRSSTab
 display RSS Ajax with tab
 design by https://goragod.com (goragod wiriya)
 20-09-56
 */
var GRSS = GClass.create();
GRSS.prototype = {
  initialize: function(feedurl, options) {
    this.feed = feedurl;
    this.options = {
      rows: 2,
      cols: 2,
      imageWidth: 75,
      className: 'table_rss_class',
      detaillen: 90,
      image: true
    };
    Object.extend(this.options, options || {});
  },
  show: function(div, interval) {
    var query = 'class=Widgets\\Rss\\Controllers\\Reader&method=get';
    query += '&url=' + this.feed;
    query += '&rows=' + this.options.rows;
    query += '&cols=' + this.options.cols;
    query += '&imageWidth=' + this.options.imageWidth;
    query += '&className=' + this.options.className;
    query += '&detaillen=' + this.options.detaillen;
    query += '&rssimage=' + this.options.image;
    var _callback = function(xhr) {
      if ($E(div)) {
        $E(div).innerHTML = xhr.responseText;
      } else {
        req.abort();
      }
    };
    var req = new GAjax();
    if (floatval(interval) == 0) {
      req.send(WEB_URL + 'xhr.php', query, _callback);
    } else {
      req.autoupdate(WEB_URL + 'xhr.php', interval, query, _callback);
    }
  }
};
var GRSSTab = GClass.create();
GRSSTab.prototype = {
  initialize: function(tab, div, interval, loadingClass) {
    this.tab = $E(tab);
    this.tab.innerHTML = '';
    this.div = $E(div);
    this.div.innerHTML = '';
    this.div.style.position = 'relative';
    this.options = new Array();
    this.feeds = new Array();
    this.texts = new Array();
    this.loadingClass = loadingClass || 'rsstab_loading';
    this.selectIndex = -1;
    this.interval = 1000 * (interval || 0);
    this.ajax = new GAjax();
    this.timer = 0;
  },
  add: function(feedurl, text, options) {
    this.feeds.push(feedurl);
    this.texts.push(text);
    var _options = {
      rows: 2,
      cols: 2,
      imageWidth: 75,
      className: 'table_rsstab_class',
      detaillen: 90,
      image: true
    };
    Object.extend(_options, options || {});
    this.options.push(_options);
  },
  show: function(id) {
    if (this.feeds.length > 0) {
      var ul = document.createElement('ul');
      this.tab.appendChild(ul);
      var temp = this;
      var tab = this.tab.id + '_';
      forEach(this.feeds, function(item, index) {
        var li = document.createElement('li');
        ul.appendChild(li);
        var a = document.createElement('a');
        a.innerHTML = temp.texts[index];
        a.href = '#' + index;
        a.id = tab + index;
        li.appendChild(a);
        a.onclick = function() {
          temp.interval = 0;
          if (temp.timer > 0) {
            window.clearTimeout(temp.timer);
          }
          var ids = this.href.split('#');
          temp.select(floatval(ids[1]));
          return false;
        };
      });
      var li = document.createElement('li');
      ul.appendChild(li);
      this.loading = li;
      this.select(id);
    }
  },
  select: function(id) {
    this._showLoading(id);
    var temp = this;
    this.ajax.send(WEB_URL + 'xhr.php', this._query(id), function(xhr) {
      temp.div.innerHTML = xhr.responseText;
      temp._setselect(id);
      if (temp.interval > 0) {
        temp.timer = window.setTimeout(function() {
          temp.selectIndex++;
          if (temp.selectIndex >= temp.feeds.length) {
            temp.selectIndex = 0;
          }
          temp.select(temp.selectIndex);
        }, temp.interval);
      }
    });
  },
  _query: function(id) {
    var query = 'class=Widgets\\Rss\\Controllers\\Reader&method=get';
    query += '&url=' + encodeURIComponent(this.feeds[id]);
    query += '&rows=' + this.options[id].rows;
    query += '&cols=' + this.options[id].cols;
    query += '&imageWidth=' + this.options[id].imageWidth;
    query += '&className=' + this.options[id].className;
    query += '&detaillen=' + this.options[id].detaillen;
    query += '&rssimage=' + this.options[id].image;
    return query;
  },
  _setselect: function(id) {
    this.selectIndex = id;
    var tab = this.tab.id + '_' + id;
    forEach(this.tab.getElementsByTagName('a'), function() {
      this.className = this.id == tab ? 'select' : '';
    });
  },
  _showLoading: function(id) {
    var tab = this.tab.id + '_' + id;
    var self = this;
    forEach(this.tab.getElementsByTagName('a'), function() {
      this.className = this.id == tab ? self.loadingClass : '';
    });
  }
};
