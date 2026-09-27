/**
 * Event module — frontend
 * The month calendar itself is rendered by the Now.js EventCalendar
 * component (auto-inits on [data-event-calendar], fetches from data-api).
 * This file only provides the click callback that opens the selected event.
 */
(function () {
  'use strict';

  // Referenced by data-on-event-click="eventCalendarNavigate" in the
  // calendar container (see modules/event/views/month.php). The component
  // passes the normalized event; the original row (with its url) is on .data.
  window.eventCalendarNavigate = function (eventData) {
    var original = (eventData && eventData.data) ? eventData.data : eventData;
    var url = original && original.url ? original.url : '';
    if (!url) {
      return;
    }
    if (window.RouterManager && typeof RouterManager.navigate === 'function') {
      // Same-origin SPA navigation when available
      try {
        var path = url.replace(WEB_URL, '');
        RouterManager.navigate('/' + path.replace(/^\/+/, ''));
        return;
      } catch (e) { /* fall through to full navigation */ }
    }
    window.location.href = url;
  };
}());
