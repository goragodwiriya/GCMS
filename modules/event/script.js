function initEventCalendar() {
  var minYear = $E('event-calendar').innerHTML;
  if (/[0-9]{4,4}/.test(minYear) == false) {
    minYear = new Date().getFullYear();
  }
  $E('event-calendar').innerHTML = '';
  new Calendar("event-calendar", {
    url: WEB_URL + "index.php/event/model/calendar/toJSON",
    minYear: minYear
  });
}
