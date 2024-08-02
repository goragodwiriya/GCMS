/**
 * GSwipe
 * Javascript Swipe
 *
 * @filesource js/swipe.js
 * @link https://www.kotchasan.com/
 * @copyright 2019 Goragod.com
 * @license https://www.kotchasan.com/license/
 */
(function() {
  'use strict';
  window.GSwipe = GClass.create();
  GSwipe.prototype = {
    initialize: function(id, options) {
      this.options = {
        mouseOffset: 5,
        beginDrag: $K.emptyFunction,
        moveDrag: $K.emptyFunction,
        endDrag: $K.emptyFunction
      };
      for (var property in options) {
        this.options[property] = options[property];
      }
      this.scroller = $G(id);
      this.active = false;
      this.swipeDir = false;
      var self = this;

      function swipeMove(e) {
        var touch = e;
        if (e.type == 'touchmove') {
          touch = e.targetTouches[0] || e.changedTouches[0];
        }
        e.mousePos = {
          x: touch.pageX,
          y: touch.pageY
        };
        var x = e.mousePos.x - self.mousePos.x,
          y = e.mousePos.y - self.mousePos.y;
        if (self.swipeDir === false) {
          if (y < -self.options.mouseOffset) {
            self.swipeDir = 'up';
          } else if (y > self.options.mouseOffset) {
            self.swipeDir = 'down';
          } else if (x < -self.options.mouseOffset) {
            self.swipeDir = 'left';
          } else if (x > self.options.mouseOffset) {
            self.swipeDir = 'right';
          }
        }
        if (self.options.moveDrag.call(self, e) === false) {
          GEvent.stop(e);
        }
      }

      function swipeEnd(e) {
        if (self.active) {
          self.active = false;
          self.scroller.removeEvent('mousemove,touchmove', swipeMove, false);
          document.removeEvent('mouseup,touchend', swipeEnd, false);
          document.removeEvent('selectstart,dragstart', cancelEvent);
          var touch = e;
          if (e.type == 'touchend') {
            touch = e.targetTouches[0] || e.changedTouches[0];
          }
          e.mousePos = {
            x: touch.pageX,
            y: touch.pageY
          };
          window.setTimeout(function() {
            self.options.endDrag.call(self, e);
            self.swipeDir = false;
          }, 1);
        }
      }

      function cancelEvent(e) {
        GEvent.stop(e);
      }

      function swipeStart(e) {
        self.active = true;
        self.scroller.addEvent('mousemove,touchmove', swipeMove, false);
        document.addEvent('mouseup,touchend', swipeEnd, false);
        document.addEvent('selectstart,dragstart', cancelEvent);
        if (self.options.beginDrag.call(self, e) === false) {
          GEvent.stop(e);
        }
      }

      function doMousedown(e) {
        if (GEvent.isLeftClick(e)) {
          self.mousePos = GEvent.pointer(e);
          swipeStart(e);
        }
      }

      function doTouchstart(e) {
        var touch = e.targetTouches[0] || e.changedTouches[0];
        self.mousePos = {
          x: touch.pageX,
          y: touch.pageY
        };
        swipeStart(e);
      }

      function doClick(e) {
        if (self.swipeDir !== false) {
          GEvent.stop(e);
        }
      }
      this.scroller.addEvent('mousedown', doMousedown, false);
      this.scroller.addEvent('touchstart', doTouchstart, false);
      this.scroller.addEvent('click', doClick, false);
    }
  };
})();
