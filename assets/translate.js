window.googleTranslateElementInit = function () {
  if (!window.google || !google.translate) {
    return;
  }

  new google.translate.TranslateElement(
    {
      pageLanguage: "en",
      includedLanguages:
        "en,es,fr,de,it,nl,pt,sv,da,no,fi,ja,ko,zh-CN,zh-TW,ar,pl,tr,id,vi,th,ms,ru,uk,hi,bn,ur,fa,he,cs,ro,hu,el",
      autoDisplay: false,
    },
    "google_translate_element"
  );
};
