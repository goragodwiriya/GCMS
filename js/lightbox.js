window.GLightbox = GClass.create();
GLightbox.prototype = {
  initialize: function(options) {
    this.id = 'gslide_div';
    this.btnclose = 'btnclose';
    this.backgroundClass = 'modalbg';
    this.previewClass = 'gallery_preview';
    this.loadingClass = 'spinner';
    this.onshow = null;
    this.onhide = null;
    this.onclose = null;
    this.ondownload = null;
    for (var property in options) {
      this[property] = options[property];
    }
    var self = this,
      checkESCkey = function(e) {
        var k = GEvent.keyCode(e);
        if (k == 27) {
          self.hide(e);
        } else if (k == 37) {
          self.showPrev(e);
        } else if (k == 39) {
          self.showNext(e);
        }
      },
      container_div = 'GLightbox_' + this.id,
      doc = $G(document);
    doc.addEvent('keydown', checkESCkey);
    if (!$E(container_div)) {
      var div = doc.createElement('div');
      doc.body.appendChild(div);
      div.id = container_div;
      div.style.left = '100%';
      div.style.top = '0';
      div.style.width = '100%';
      div.style.height = '100vh';
      div.style.position = 'fixed';
      div.style.zIndex = 1000;
      var c = doc.createElement('div');
      div.appendChild(c);
      c.className = this.id;
      c.style.position = 'fixed';
      var c2 = doc.createElement('figure');
      c.appendChild(c2);
      c2.className = this.previewClass;
      this.img = doc.createElement('img');
      this.img.alt = '';
      c2.appendChild(this.img);
      new GDragMove(c, this.img);
      c = doc.createElement('figcaption');
      div.appendChild(c);
      this.loading = doc.createElement('span');
      div.appendChild(this.loading);
      this.loading.className = this.loadingClass;
      this.caption = doc.createElement('p');
      c.appendChild(this.caption);
      var btnclose = doc.createElement('span');
      div.appendChild(btnclose);
      btnclose.className = this.btnclose;
      btnclose.title = trans('Close');
      callClick(btnclose, function() {
        self.hide();
      });
      this.zoom = doc.createElement('span');
      div.appendChild(this.zoom);
      this.zoom.id = 'GLightbox_zoom';
      callClick(this.zoom, function(e) {
        self._fullScreen(e);
      });
      this.prev = doc.createElement('span');
      div.appendChild(this.prev);
      this.prev.className = 'hidden';
      this.prev.title = trans('Prev');
      callClick(this.prev, function() {
        self.showPrev();
      });
      this.next = doc.createElement('span');
      div.appendChild(this.next);
      this.next.className = 'hidden';
      this.next.title = trans('Next');
      callClick(this.next, function() {
        self.showNext();
      });
      this.download = doc.createElement('a');
      div.appendChild(this.download);
      this.download.title = trans('Download');
      this.download.href = '#';
      if (Object.isFunction(this.ondownload)) {
        callClick(this.download, function() {
          self.ondownload(this.href);
          return false;
        });
      }
    }
    this.zoom = $E('GLightbox_zoom');
    this.div = $G(container_div);
    this.body = $G(this.div.firstChild);
    this.preview = $G(this.body.firstChild);
    this.img = this.preview.firstChild;
    this.body.style.overflow = 'hidden';
    this.currentId = 0;
    this.imgs = [];
  },
  clear: function() {
    this.currentId = 0;
    this.imgs.length = 0;
    return this;
  },
  add: function(picture, detail, url) {
    const datas = {
      picture: picture,
      detail: detail,
      url: url
    };
    const id = this.imgs.length;
    this.imgs.push(datas);
    return id;
  },
  fromJSON: function(datas) {
    const self = this;
    this.clear();
    datas.forEach(function(item) {
      if (typeof item == 'string') {
        self.add(item);
      } else {
        self.add(item.picture, item.detail, item.url);
      }
    });
    return this;
  },
  play: function(id) {
    if (id && this.imgs[id]) {
      this.currentId = id;
    }
    return this.show(this.currentId, false);
  },
  showNext: function() {
    if (this.div.style.display == 'block' && this.imgs.length > 0) {
      this.currentId++;
      if (this.currentId >= this.imgs.length) {
        this.currentId = 0;
      }
      this.show(this.currentId, false);
    }
  },
  showPrev: function() {
    if (this.div.style.display == 'block' && this.imgs.length > 0) {
      this.currentId--;
      if (this.currentId < 0) {
        this.currentId = this.imgs.length - 1;
      }
      this.show(this.currentId, false);
    }
  },
  _fullScreen: function() {
    if (this.div.style.display == 'block' && this.imgs.length > 0) {
      this.show(this.currentId, this.zoom.className == 'btnnav zoomout');
    }
  },
  show: function(index, fullscreen) {
    const self = this,
      img = this.imgs[index];
    this.overlay();
    this.zoom.className = fullscreen ? 'btnnav zoomin' : 'btnnav zoomout';
    this.zoom.title = trans(fullscreen ? 'fit screen' : 'full image');
    this.loading.className = this.loadingClass + ' show';
    if (this.currentId == 0) {
      this.prev.addClass('hide');
    } else if (this.prev.className != 'btnnav prev') {
      this.prev.className = 'btnnav prev hide';
    }
    if (this.currentId == this.imgs.length - 1) {
      this.next.addClass('hide');
    } else if (this.next.className != 'btnnav next') {
      this.next.className = 'btnnav next hide';
    }
    var ds = /.*\/([^\/]+\.[a-zA-Z]{3,4})$/.exec(img.picture);
    if (ds) {
      this.download.className = 'btnnav download';
      this.download.href = img.picture;
      this.download.download = ds[1];
    } else {
      this.download.className = 'hidden';
    }
    window.setTimeout(function() {
      if (self.currentId == 0) {
        self.prev.className = 'hidden';
      } else {
        self.prev.className = 'btnnav prev';
      }
      if (self.currentId == self.imgs.length - 1) {
        self.next.className = 'hidden';
      } else {
        self.next.className = 'btnnav next';
      }
    }, 500);
    new preload(img.picture, function() {
      self.loading.className = self.loadingClass;
      self.img.src = this.src;
      if (!fullscreen) {
        var w = this.width;
        var h = this.height;
        var dm = self.body.getDimensions();
        var hOffset =
          dm.height -
          self.body.getClientHeight() +
          parseInt(self.body.getStyle('marginTop')) +
          parseInt(self.body.getStyle('marginBottom'));
        var wOffset =
          dm.width -
          self.body.getClientWidth() +
          parseInt(self.body.getStyle('marginLeft')) +
          parseInt(self.body.getStyle('marginRight'));
        var src_h = document.viewport.getHeight() - hOffset - 20;
        var src_w = document.viewport.getWidth() - wOffset - 20;
        var nw, nh;
        if (h > src_h) {
          nh = src_h;
          nw = (src_h * w) / h;
        } else if (w > src_w) {
          nw = src_w;
          nh = (src_w * h) / w;
        } else {
          nw = w;
          nh = h;
        }
        if (nw > src_w) {
          nw = src_w;
          nh = (src_w * h) / w;
        } else if (nh > src_h) {
          nh = src_h;
          nw = (src_h * w) / h;
        }
        self.img.style.width = nw + 'px';
        self.img.style.height = nh + 'px';
      } else {
        self.img.style.width = 'auto';
        self.img.style.height = 'auto';
      }
      if (img.title && img.title != '') {
        self.caption.innerHTML = img.title.replace(/[\n]/g, '<br>');
        self.caption.parentNode.className = 'show';
      } else {
        self.caption.parentNode.className = '';
      }
      self.div.style.display = 'block';
      self.div.firstChild.center();
      self.div.style.left = 0;
      self.div.fadeIn(function() {
        self._show.call(self);
      });
    });
    return this;
  },
  hide: function() {
    if (Object.isFunction(this.onhide)) {
      this.onhide.call(this);
    }
    var self = this;
    this.div.fadeOut();
    this.iframe.fadeOut(function() {
      self._hide.call(self);
    });
    return this;
  },
  overlay: function() {
    var frameId = 'iframe_' + this.div.id,
      self = this;
    if (!$E(frameId)) {
      var io = $G(document.body).create('iframe', {
        id: frameId,
        height: '100%',
        frameBorder: 0
      });
      io.setStyle('position', 'absolute');
      io.setStyle('zIndex', 999);
      io.className = this.backgroundClass;
      io.style.display = 'none';
    }
    this.iframe = $G(frameId);
    if (this.iframe.style.display == 'none') {
      this.iframe.style.left = '0px';
      this.iframe.style.top = '0px';
      this.iframe.style.display = 'block';
      this.iframe.fadeIn();
      $G(self.iframe.contentWindow.document).addEvent('click', function(e) {
        self.hide();
      });
      var d = $G(document).getDimensions();
      this.iframe.style.height = d.height + 'px';
      this.iframe.style.width = '100%';
    }
    return this;
  },
  _show: function() {
    if (Object.isFunction(this.onshow)) {
      this.onshow.call(this);
    }
  },
  _hide: function() {
    this.iframe.style.display = 'none';
    this.div.style.display = 'none';
    if (Object.isFunction(this.onclose)) {
      this.onclose.call(this);
    }
  }
};
