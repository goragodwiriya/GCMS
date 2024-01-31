function initProductWrite(languages, tab, module_id, module) {
  initWriteTab("accordient_menu", tab);
  checkSaved("preview", WEB_URL + "index.php?module=" + module, "id");
  new GValidator(
    "alias",
    "keyup,change",
    checkAlias,
    "index.php/index/model/checker/alias",
    null,
    "setup_frm"
  );
  forEach(languages, function(item) {
    initAutoComplete(
      "keywords_" + item,
      WEB_URL + "index.php/document/model/autocomplete/findTag",
      "tag",
      "tags", {
        get: function() {
          return "search=" + encodeURIComponent($E("keywords_" + item).value);
        },
        callBack: function() {
          $E("keywords_" + item).value = this.tag.unentityify();
        },
        onSuccess: function() {
          var input = $G("keywords_" + item);
          input.inputGroup.addItem(this.datas.tag, this.datas.tag);
          input.value = "";
          input.focus();
        }
      }
    );
  });
}
