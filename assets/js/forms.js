function q(sel){ return document.querySelector(sel); }
function show(el, yes){ if(el) el.classList.toggle("hidden", !yes); }

function updateTypeBlocks(){
  const type = q("#participant_type")?.value || "";
  show(q("#block_speaker"), type === "speaker");
  show(q("#block_investrep"), type === "investrep");
  show(q("#block_investor"), type === "investor");
  show(q("#block_sponsor"), type === "sponsor");
}

document.addEventListener("DOMContentLoaded", ()=>{
  const loadedAt = q('input[name="form_loaded_at"]');
  if(loadedAt) loadedAt.value = String(Date.now());

  const jsEnabled = q('input[name="js_enabled"]');
  if(jsEnabled) jsEnabled.value = "1";

  const params = new URL(location.href).searchParams;
  const typeParam = params.get("type");
  const levelParam = params.get("level");

  const typeSel = q("#participant_type");
  if(typeSel && typeParam){
    const has = [...typeSel.options].some(o => o.value === typeParam);
    if(has) typeSel.value = typeParam;
  }

  const sponsorLevel = q("#sponsor_level");
  if(sponsorLevel && levelParam){
    const has2 = [...sponsorLevel.options].some(o => o.value === levelParam);
    if(has2) sponsorLevel.value = levelParam;
  }

  if(typeSel){
    typeSel.addEventListener("change", updateTypeBlocks);
    updateTypeBlocks();
  }

  const inviteNeed = q("#invite_need");
  const inviteWrap = q("#invite_comment_wrap");
  if(inviteNeed && inviteWrap){
    const refresh = ()=> show(inviteWrap, inviteNeed.value === "yes");
    inviteNeed.addEventListener("change", refresh);
    refresh();
  }
});
