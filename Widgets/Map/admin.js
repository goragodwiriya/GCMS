var my_map, my_marker;

function findMe() {
  if (navigator.geolocation) {
    var options = {
      enableHighAccuracy: true,
      timeout: 5000,
      maximumAge: 0
    };

    function error(err) {
      alert(err.message);
    }
    navigator.geolocation.getCurrentPosition(function(pos) {
      var myLatlng = new google.maps.LatLng(pos.coords.latitude, pos.coords.longitude);
      my_map.setCenter(myLatlng);
      my_marker.setPosition(myLatlng);
      mapChanged();
    }, error, options);
  }
}

function findLocation() {
  var search = prompt(trans('Enter a place name nearby the location to search'), 'Bangkok');
  if (search !== null && search !== '') {
    var geocoder = new google.maps.Geocoder();
    geocoder.geocode({
      address: search
    }, function(results, status) {
      if (status == google.maps.GeocoderStatus.OK) {
        var myLatlng = results[0].geometry.location;
        my_map.setCenter(myLatlng);
        my_marker.setPosition(myLatlng);
        mapChanged();
      } else {
        alert(trans('Sorry XXX not found').replace(/XXX/, trans('Location')));
      }
    });
  }
}

function initMapDemo(key, lng) {
  loadJavascript('map', '//maps.google.com/maps/api/js?key=' + key + '&amp;language=' + lng);
  window.setTimeout(function() {
    var myLatlng;
    if ($E('map_latitude') && $E('map_latitude')) {
      myLatlng = new google.maps.LatLng($E('map_latitude').value, $E('map_lantitude').value);
    } else {
      myLatlng = new google.maps.LatLng($E('map_info_latitude').value, $E('map_info_lantitude').value);
    }
    var o = {
      zoom: floatval($E("map_zoom").value),
      center: myLatlng,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    };
    my_map = new google.maps.Map($E("map_canvas"), o);
    google.maps.event.addListener(my_map, "zoom_changed", function() {
      var p = my_marker.getPosition();
      my_map.panTo(p);
      mapChanged();
    });
    google.maps.event.addListener(my_map, "dragend", function() {
      mapChanged();
    });
    google.maps.event.addListener(my_map, "zoom_changed", function() {
      mapChanged();
    });
    var info = new google.maps.LatLng($E('map_info_latitude').value, $E('map_info_lantitude').value);
    my_marker = new google.maps.Marker({
      position: info,
      map: my_map,
      draggable: true,
      title: trans('Drag the marker to the location you want')
    });
    google.maps.event.addListener(my_marker, "dragend", function() {
      var p = my_marker.getPosition();
      my_map.panTo(p);
      mapChanged();
    });
    var posChanged = function() {
      var p = new google.maps.LatLng($E('map_info_latitude').value, $E('map_info_lantitude').value);
      my_marker.setPosition(p);
      my_map.panTo(p);
    };
    $G('map_info_latitude').addEvent('change', posChanged);
    $G('map_info_lantitude').addEvent('change', posChanged);
    if (navigator.geolocation) {
      $G('find_me').removeClass('hidden');
      callClick("find_me", findMe);
      callClick("map_search", findLocation);
    }
  }, 1000);
}

function mapChanged() {
  var p = my_marker.getPosition();
  $E("map_info_latitude").value = p.lat();
  $E("map_info_lantitude").value = p.lng();
  var c = my_map.getCenter();
  if ($E('map_latitude')) {
    $E("map_latitude").value = c.lat();
  }
  if ($E('map_lantitude')) {
    $E("map_lantitude").value = c.lng();
  }
  $E("map_zoom").value = my_map.getZoom();
}
