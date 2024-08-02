/**
 RSSGal
 display RSS Gallery from http://gallery.gcms.in.th
 for GCMS 4.0.0
 design by https://goragod.com (goragod wiriya)
 8-11-53
 */
RSSGal = GClass.create();
RSSGal.prototype = {
  initialize: function(options) {
    this.options = {
      rows: 3,
      cols: 2,
      className: 'table_rss_gallery_class',
      url: 'http://gallery.gcms.in.th/gallery.rss'
    };
    Object.extend(this.options, options || {});
  },
  show: function(div) {
    var query = 'class=Widgets\\Gallery\\Controllers\\Reader&method=get';
    query += '&url=' + encodeURIComponent(this.options.url);
    query += '&rows=' + this.options.rows;
    query += '&cols=' + this.options.cols;
    query += '&className=' + this.options.className;
    var _callback = function(xhr) {
      $G(div).setHTML(xhr.responseText);
    };
    new GAjax().send(WEB_URL + 'xhr.php', query, _callback);
  }
};
