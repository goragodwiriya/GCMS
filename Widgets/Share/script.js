// widgets/share/script.js
var share_patt = /(fb|twitter|line)_share/;
var last_get_share = "";
var doShare = function(e) {
  GEvent.stop(e);
  var u = this.getAttribute("data-url");
  var t = this.getAttribute("data-title");
  if (u == null || u == "") {
    u = encodeURIComponent(getCurrentURL());
  }
  if (t == null || t == "") {
    t = encodeURIComponent(window.document.title);
  }
  var hs = share_patt.exec(this.className);
  if (hs[1] == "fb") {
    window.open("https://www.facebook.com/sharer.php?u=" + u + "&t=" + t, "sharer", "toolbar=0,status=0,width=626,height=436");
    last_get_share = "";
    getShareCount(u);
  } else if (hs[1] == "twitter") {
    window.open("https://www.twitter.com/share?url=" + u + "&text=" + t, "sharer", "toolbar=0,status=0,width=626,height=436");
  } else if (hs[1] == "line") {
    window.open("line://msg/text/" + t + "%0D%0A" + u, "sharer");
  }
};

function initShareButton(id) {
  forEach($E(id).getElementsByTagName("*"), function() {
    var hs = share_patt.exec(this.className);
    if (hs) {
      if (hs[1] == "line" && !$K.isMobile()) {
        this.className = 'hidden';
      } else {
        callClick(this, doShare);
        if (hs[1] == "fb") {
          getShareCount();
        }
      }
    }
  });
}

function getShareCount(url) {
  if (url == null) {
    url = encodeURIComponent(getCurrentURL());
  }
  window.setTimeout(function() {
    if ($E("fb_share_count")) {
      if (last_get_share != url) {
        last_get_share = url;
        send(WEB_URL + "xhr.php", "class=Widgets\\Share\\Controllers\\Xhr&method=get&url=" + url, function(xhr) {
          $E("fb_share_count").innerHTML = xhr.responseText;
        });
      }
    }
  }, 1000);
}
