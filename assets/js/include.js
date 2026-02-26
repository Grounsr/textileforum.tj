async function includePart(targetId, url){
  const el = document.getElementById(targetId);
  if(!el) return;
  const r = await fetch(url, {cache:"no-cache"});
  el.innerHTML = await r.text();
}

document.addEventListener("DOMContentLoaded", async ()=>{
  await includePart("site-header", "/partials/header.html");
  await includePart("site-footer", "/partials/footer.html");
  document.dispatchEvent(new CustomEvent("partials:loaded"));
});
