function initPersonnelWidget(id) {
  $G(window).Ready(function() {
    var itemIndex = 0;
    var divs = new Array();
    var div = $G(id);
    var play = true;
    forEach(div.elems('div'), function(item, index) {
      if ($G(item).hasClass('currItem item')) {
        item.id = index;
        item.style.position = 'absolute';
        divs.push(item);
        item.addEvent('mouseover', function() {
          play = false;
        });
        item.addEvent('mouseout', function() {
          play = true;
        });
      }
    });
    div.style.height = (floatval(div.getStyle('paddingTop')) + floatval(div.getStyle('paddingBottom')) + divs[itemIndex].getHeight()) + 'px';
    window.setInterval(function() {
      if (play) {
        forEach(divs, function() {
          this.className = this.id == itemIndex ? 'currItem' : 'item';
          if (this.id == itemIndex) {
            div.style.height = (floatval(div.getStyle('paddingTop')) + floatval(div.getStyle('paddingBottom')) + this.getHeight()) + 'px';
          }
        });
        itemIndex = itemIndex == divs.length - 1 ? 0 : itemIndex + 1;
      }
    }, 5000);
  });
}
