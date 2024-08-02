function initEvent() {
  var doChanged = function() {
    $E("to_time").disabled = this.checked;
  };
  $G("forever").addEvent("change", doChanged);
  doChanged.call($E("forever"));
}
