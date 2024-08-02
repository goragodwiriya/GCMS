var VScroller = GClass.create();
VScroller.prototype = {
  initialize: function(id, options) {
    this.options = {
      className: 'vscroller',
      itemClass: 'item',
      scrollerClass: 'scrollable'
    };
    for (var property in options) {
      this.options[property] = options[property];
    }
    this.container = $G(id);
    this.container.addClass(this.options.className);
    this.scroller = $G(this.container.getElementsByClassName(this.options.scrollerClass)[0]);
    this.position = '';
    this.htmlElement = $G(document.querySelector('html'));
    this.smooth = this.htmlElement.hasClass('smooth');
    this.next = this.container.create('span');
    this.next.className = 'btnnav next';
    this.next.title = trans('Next');
    callClick(this.next, function() {
      move('next');
    });
    this.prev = this.container.create('span');
    this.prev.className = 'btnnav prev';
    this.prev.title = trans('Prev');
    callClick(this.prev, function() {
      move('prev');
    });
    var tmp = this;

    function doResize() {
      tmp.scroller.style.width = null;
      var items = tmp.scroller.getElementsByClassName(tmp.options.itemClass),
        cw = tmp.container.getWidth(),
        w = 0.00;
      forEach(items, function() {
        this.style.width = null;
        w += $G(this).getWidth();
      });
      var itemWidth = ((w / items.length) * 100) / cw;
      tmp.scroller.style.width = w + 'px';
      forEach(items, function() {
        this.style.width = ((cw * itemWidth) / 100) + 'px';
      });
      if (tmp.scroller.getWidth() <= tmp.container.getWidth()) {
        tmp.prev.style.display = 'none';
        tmp.next.style.display = 'none';
      } else {
        tmp.prev.style.display = 'block';
        tmp.next.style.display = 'block';
      }
      tmp.scroller.style.left = '0px';
      checkButton()
    }
    $G(window).addEvent('resize', doResize);
    doResize();

    function move(page) {
      var cw = tmp.container.getStyle('width').toInt(),
        cl = tmp.container.getLeft(),
        sl = tmp.scroller.getLeft(),
        l,
        w;
      forEach(tmp.scroller.getElementsByClassName(tmp.options.itemClass), function() {
        l = this.getLeft();
        w = this.offsetWidth;
        if (page == 'next') {
          if (l + w - cl > cw) {
            moveTo(sl - l);
            return true;
          }
        } else if (page == 'prev') {
          if (l > 0) {
            moveTo(sl + cw - l);
            return true;
          }
        }
      });
    }

    function moveTo(x) {
      tmp.scroller.addClass('animate');
      var cw = tmp.container.getWidth() - tmp.scroller.getWidth();
      tmp.scroller.style.left = Math.min(0, Math.max(cw, x)) + 'px';
      window.setTimeout(function() {
        tmp.scroller.removeClass('animate');
        checkButton();
      }, 500);
    }

    function checkButton() {
      var l = tmp.scroller.getLeft() - tmp.container.getLeft(),
        cw = tmp.container.getWidth() - tmp.scroller.getWidth();
      if (l == 0) {
        tmp.prev.addClass('hide');
      } else {
        tmp.prev.removeClass('hide');
      }
      if (l - cw < 1) {
        tmp.next.addClass('hide');
      } else {
        tmp.next.removeClass('hide');
      }
    }
    checkButton();
    new GSwipe(this.scroller, {
      beginDrag: function() {
        tmp.mouseOffset = {
          x: this.mousePos.x - tmp.scroller.getStyle('left').toInt(),
          y: this.mousePos.y - tmp.scroller.getStyle('top').toInt()
        };
        if (tmp.smooth) {
          tmp.htmlElement.removeClass('smooth');
        }
      },
      moveDrag: function(e) {
        GEvent.element(e).drag = true;
        if (this.swipeDir == 'left' || this.swipeDir == 'right') {
          var cw = tmp.container.getWidth() - tmp.scroller.getWidth();
          if (cw < 0) {
            tmp.scroller.style.left = Math.min(0, Math.max(cw, e.mousePos.x - tmp.mouseOffset.x)) + 'px';
          }
          return false;
        } else if (this.swipeDir == 'up' || this.swipeDir == 'down') {
          var doc = document.documentElement,
            left = (window.pageXOffset || doc.scrollLeft) - (doc.clientLeft || 0),
            top = (window.pageYOffset || doc.scrollTop) - (doc.clientTop || 0);
          window.scroll({
            left: left,
            top: top - (e.mousePos.y - tmp.mouseOffset.y)
          });
          return false;
        }
      },
      endDrag: function(e) {
        var src = GEvent.element(e),
          container_l = tmp.container.getLeft(),
          cw = tmp.container.getWidth(),
          offset = cw - tmp.scroller.getWidth(),
          curr = 0,
          self = this,
          l,
          w;
        forEach(tmp.scroller.getElementsByClassName(tmp.options.itemClass), function() {
          l = this.getLeft();
          w = this.offsetWidth;
          if (self.swipeDir == 'left' && l > container_l) {
            moveTo(Math.max(offset, 0 - curr));
            return false;
          } else if (self.swipeDir == 'right' && l + w - container_l > cw) {
            moveTo(Math.min(0, cw - curr));
            return false;
          } else {
            curr += w;
          }
        });
        if (tmp.smooth) {
          tmp.htmlElement.addClass('smooth');
        }
        window.setTimeout(function() {
          src.drag = false;
        }, 1);
      }
    });
  }
};
