var share_patt = /(fb|twitter|line)_share/;

/**
 * Check if it is a mobile device or not.
 * Use Now.js's window.Utils if available, otherwise use UA regex.
 */
function shareIsMobile() {
  if (window.Utils && window.Utils.browser && typeof window.Utils.browser.isMobile === 'function') {
    return window.Utils.browser.isMobile();
  }
  return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

/**
 * Open the social media sharing window.
 */
function doShare(e) {
  e.preventDefault();

  /* Retrieve URL and Title values ​​from data attributes or use the current page */
  var u = this.getAttribute('data-url');
  var t = this.getAttribute('data-title');

  if (!u) {
    u = encodeURIComponent(window.location.href);
  }
  if (!t) {
    t = encodeURIComponent(document.title);
  }

  /* Check where it is being shared. */
  var hs = share_patt.exec(this.className);
  if (!hs) return;

  if (hs[1] === 'fb') {
    /* Share to Facebook */
    window.open(
      'https://www.facebook.com/sharer.php?u=' + u,
      'fb_sharer',
      'toolbar=0,status=0,width=626,height=436'
    );
  } else if (hs[1] === 'twitter') {
    /* Share to X (Twitter) */
    window.open(
      'https://twitter.com/intent/tweet?url=' + u + '&text=' + t,
      'tw_sharer',
      'toolbar=0,status=0,width=626,height=436'
    );
  } else if (hs[1] === 'line') {
    /* Share to LINE */
    if (shareIsMobile()) {
      /* Mobile: Open the LINE app directly. */
      window.open('line://msg/text/' + t + '%0D%0A' + u, '_blank');
    } else {
      /* Desktop: Use LINE Social Plugin URL. */
      window.open(
        'https://social-plugins.line.me/lineit/share?url=' + u,
        'line_sharer',
        'toolbar=0,status=0,width=626,height=436'
      );
    }
  }
}

/**
 * Initialize a share button in the specified container.
 * @param {string} id -ID ของ container element
 */
function initShareButton(id) {
  var container = document.getElementById(id);
  if (!container) return;

  Array.from(container.getElementsByTagName('a')).forEach(function(el) {
    var hs = share_patt.exec(el.className);
    if (hs) {
      el.addEventListener('click', doShare);
    }
  });
}