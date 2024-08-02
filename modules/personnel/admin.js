function initPersonnel() {
  var patt = /order_([0-9]+)_([0-9]+)/;
  forEach($G("datatable").elems("input"), function() {
    if (patt.test(this.id)) {
      $G(this).addEvent("change", function() {
        var hs = patt.exec(this.id);
        if (hs) {
          send(
            "../xhr.php/personnel/model/admin/setup/action",
            "action=order&mid=" +
            hs[1] +
            "&id=" +
            hs[2] +
            "&value=" +
            this.value,
            doFormSubmit
          );
        }
      });
    }
  });
}
