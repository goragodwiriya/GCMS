function initWidgetCalendar(id, owner, module) {
  var q = "class=Widgets\\Calendar\\Models\\Get&method=toJSON&owner=" + owner;
  if (module != "") {
    q += "&module=" + module;
  }
  new Calendar(id, {
    class: "widget-calendar",
    url: WEB_URL + "xhr.php",
    params: q,
    showToday: true
  });
}
