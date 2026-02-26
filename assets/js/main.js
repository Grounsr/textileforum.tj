function initBurger(){
  const burger = document.querySelector("[data-burger]");
  const drawer = document.querySelector("[data-drawer]");
  if(!burger || !drawer) return;

  burger.addEventListener("click", ()=>{
    const expanded = burger.getAttribute("aria-expanded") === "true";
    burger.setAttribute("aria-expanded", String(!expanded));
    drawer.hidden = expanded;
  });
}

function initI18n(){
  const lang = getLang();
  applyI18n(lang);
}

document.addEventListener("DOMContentLoaded", ()=>{
  initI18n();
});

document.addEventListener("partials:loaded", ()=>{
  initI18n();
  initBurger();
});
