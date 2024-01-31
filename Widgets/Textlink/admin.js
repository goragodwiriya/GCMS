// Widgets/Textlink/admin.js
function initTextlinkWrite() {
  callClick("dateless", function() {
    $E("publish_end").disabled = this.checked ? true : false;
  });
  var _stylesChanged = function() {
    $E("template").disabled = this.value != "custom";
    send(
      "index.php/Widgets/Textlink/Models/Action/get",
      "action=styles&val=" + this.value + "&id=" + $E("id").value,
      function(xhr) {
        $E("template").value = xhr.responseText;
        doTextlinkDemo();
      },
      $E("prefix")
    );
  };
  var doTextlinkDemo = function() {
    var v = $E("name").value;
    $E("name_demo").innerHTML =
      "{WIDGET_TEXTLINK" + (v == "" ? "" : "_" + v) + "}";
  };
  $G("name").addEvent("keyup", doTextlinkDemo);
  $G("name").addEvent("change", doTextlinkDemo);
  $G("type").addEvent("change", _stylesChanged);
  _stylesChanged.call($E("type"));
}
