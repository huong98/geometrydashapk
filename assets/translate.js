const languageRoutes = {
  vn: "vi",
  jp: "ja",
  kr: "ko",
  cn: "zh-CN",
  tw: "zh-TW",
  es: "es",
  fr: "fr",
  de: "de",
  it: "it",
  nl: "nl",
  pt: "pt",
  se: "sv",
  dk: "da",
  no: "no",
  fi: "fi",
  id: "id",
  th: "th",
  my: "ms",
  pl: "pl",
  tr: "tr",
  ru: "ru",
  ua: "uk",
  in: "hi",
  bd: "bn",
  pk: "ur",
  ir: "fa",
  sa: "ar",
  il: "he",
  cz: "cs",
  ro: "ro",
  hu: "hu",
  gr: "el",
};

const routePrefix = window.location.pathname.split("/").filter(Boolean)[0];
const routeLanguage = languageRoutes[routePrefix];

function setTranslateCookie(language) {
  const value = language ? `/en/${language}` : "";
  const maxAge = language ? "; max-age=31536000" : "; expires=Thu, 01 Jan 1970 00:00:00 GMT";
  const hostParts = window.location.hostname.split(".");
  const rootDomain =
    hostParts.length > 2 ? `.${hostParts.slice(-2).join(".")}` : window.location.hostname;

  document.cookie = `googtrans=${value}; path=/${maxAge}`;
  document.cookie = `googtrans=${value}; domain=${rootDomain}; path=/${maxAge}`;
}

function getLocalizedPath(prefix) {
  const parts = window.location.pathname.split("/").filter(Boolean);

  if (languageRoutes[parts[0]]) {
    parts.shift();
  }

  if (!prefix) {
    return `/${parts.join("/")}`;
  }

  return `/${[prefix].concat(parts).join("/")}`;
}

if (routeLanguage) {
  setTranslateCookie(routeLanguage);
}

document.addEventListener("DOMContentLoaded", function () {
  const currentLanguage = document.querySelector(".language-picker-current");

  document.querySelectorAll("[data-language-route]").forEach(function (link) {
    const prefix = link.getAttribute("data-language-route");
    const isActive = prefix === routePrefix || (!prefix && !routeLanguage);

    link.href = getLocalizedPath(prefix);
    link.classList.toggle("active", isActive);

    if (isActive && currentLanguage) {
      currentLanguage.textContent = link.textContent;
    }

    link.addEventListener("click", function () {
      setTranslateCookie(languageRoutes[prefix]);
    });
  });
});

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
