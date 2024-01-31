function initTags(id, template) {
  var ids,
    patt = /tags-([0-9]+)/,
    mtooltip = new GTooltip({
      className: "member-tooltip",
      fade: true,
      cache: true
    });
  forEach($G(id).elems('a'), function(item) {
    if (patt.exec(item.id)) {
      callClick(item, function() {
        send(WEB_URL + 'xhr.php', 'class=Widgets\\Tags\\Models\\Clicked&method=submit&id=' + this.id);
      });
      $G(item).addEvent('mouseover', function() {
        var item = ids[this.id.replace('tags-', '')];
        if (item) {
          var content = template.replace('%TAG%', item.tag).replace('%COUNT%', toCurrency(item.count, 0));
          mtooltip.show(this, content);
        }
      });
    }
  });
  send(WEB_URL + 'xhr.php', 'class=Widgets\\Tags\\Models\\Datas&method=execute', function(xhr) {
    ids = xhr.responseText.toJSON();
  });
}
