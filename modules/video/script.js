/**
 * Video module — frontend player
 * Opens a YouTube lightbox when a video card is clicked and counts the play
 * via GET api/video/view?id={id}.
 */
(function () {
  'use strict';

  function closePlayer() {
    var overlay = document.getElementById('videoPlayerOverlay');
    if (overlay) {
      overlay.remove();
      document.body.style.overflow = '';
    }
    document.removeEventListener('keydown', onKeydown);
  }

  function onKeydown(e) {
    if (e.key === 'Escape') {
      closePlayer();
    }
  }

  function openPlayer(youtube, topic) {
    closePlayer();

    var overlay = document.createElement('div');
    overlay.id = 'videoPlayerOverlay';
    overlay.className = 'video-player-overlay';
    overlay.innerHTML =
      '<div class="video-player-box" role="dialog" aria-modal="true" aria-label="' + topic.replace(/"/g, '&quot;') + '">' +
      '<button type="button" class="video-player-close" aria-label="Close">&times;</button>' +
      '<div class="video-player-frame">' +
      '<iframe src="https://www.youtube.com/embed/' + encodeURIComponent(youtube) + '?autoplay=1" ' +
      'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" ' +
      'allowfullscreen title="' + topic.replace(/"/g, '&quot;') + '"></iframe>' +
      '</div>' +
      '<div class="video-player-caption">' + topic + '</div>' +
      '</div>';

    overlay.addEventListener('click', function (e) {
      if (e.target === overlay || e.target.classList.contains('video-player-close')) {
        closePlayer();
      }
    });

    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';
    document.addEventListener('keydown', onKeydown);
  }

  function onCardClick(e) {
    var card = e.target.closest('[data-youtube]');
    if (!card) {
      return;
    }
    e.preventDefault();

    var youtube = card.dataset.youtube;
    var topic = card.dataset.topic || '';

    openPlayer(youtube, topic);

    // count the play
    var id = parseInt(card.dataset.id, 10);
    if (id > 0) {
      fetch(WEB_URL + 'api/video/view?id=' + id, {credentials: 'same-origin'})
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res && res.success && res.data) {
            var views = card.querySelector('.video-views');
            if (views) {
              views.textContent = Number(res.data.views).toLocaleString();
            }
          }
        })
        .catch(function () { /* counting is best-effort */ });
    }
  }

  document.addEventListener('click', onCardClick);
}());
