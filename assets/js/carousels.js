function scrollCarousel(track, dir){
  const step = 280;
  track.scrollBy({left: dir * step, behavior:"smooth"});
}
document.addEventListener("click", (e)=>{
  const btn = e.target.closest("[data-carousel-btn]");
  if(!btn) return;
  const id = btn.getAttribute("data-carousel-btn");
  const dir = parseInt(btn.getAttribute("data-dir"), 10) || 1;
  const track = document.querySelector(`[data-carousel-track="${id}"]`);
  if(track) scrollCarousel(track, dir);
});
