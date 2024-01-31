function initDocumentWrite(languages, module_id) {
  new GValidator(
    "alias",
    "keyup,change",
    checkAlias,
    "index.php/index/model/checker/alias",
    null,
    "setup_frm"
  );
  selectChanged(
    "category_" + module_id,
    "index.php/index/model/admincategory/action",
    doFormSubmit
  );
  forEach(languages, function(item) {
    initAutoComplete(
      "relate_" + item,
      WEB_URL + "index.php/document/model/autocomplete/findRelate",
      "relate",
      "edit", {
        get: function() {
          return (
            "search=" +
            encodeURIComponent($E("relate_" + item).value) +
            "&language=" +
            item +
            "&module_id=" +
            module_id
          );
        },
        callBack: function() {
          $E("relate_" + item).value = this.relate;
          $E("relate_" + item).focus();
        }
      }
    );
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
