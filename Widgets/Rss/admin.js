function initRssWrite() {
  var indexChanged = function() {
    var n = this.value.toInt();
    $E('rss_index_result').innerHTML = '{WIDGET_RSS' + (n == 0 ? '' : '_' + n) + '}';
  };
  $G('rss_index').addEvent('change', indexChanged);
  $G('rss_index').addEvent('keyup', indexChanged);
  indexChanged.call($E('rss_index'));
}
