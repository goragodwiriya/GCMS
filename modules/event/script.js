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
    // RouterManager is always defined by now.core, but the frontend runs with
    // router.enabled = false (js/main.js), so only hand off to it when it was
    // actually initialized — otherwise navigate() resolves without moving.
    var router = window.RouterManager;
    if (router && router.state && router.state.initialized && typeof router.navigate === 'function') {
      var path = url.replace(WEB_URL, '');
      router.navigate('/' + path.replace(/^\/+/, '')).catch(function () {
        window.location.href = url;
      });
      return;
    }
    window.location.href = url;
  };
}());
