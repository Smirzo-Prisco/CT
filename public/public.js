"use strict";(function(){const e=document.getElementById("pub-header");e&&window.addEventListener("scroll",()=>{e.classList.toggle("scrolled",window.scrollY>40)},{passive:!0})})(),(function(){const e=document.getElementById("pubHamburger"),t=document.getElementById("pubNav");!e||!t||(e.addEventListener("click",n=>{n.stopPropagation();const o=t.classList.toggle("open");e.classList.toggle("open",o),e.setAttribute("aria-expanded",o)}),t.addEventListener("click",n=>{(n.target.tagName==="A"||n.target.tagName==="BUTTON")&&(t.classList.remove("open"),e.classList.remove("open"),e.setAttribute("aria-expanded","false"))}),document.addEventListener("click",n=>{!t.contains(n.target)&&!e.contains(n.target)&&(t.classList.remove("open"),e.classList.remove("open"),e.setAttribute("aria-expanded","false"))}))})();function openModal(e){document.querySelectorAll(".pub-modal-overlay").forEach(o=>{o.style.display="none"});const t=document.getElementById(e);if(!t)return;t.style.display="flex";const n=t.querySelector("input, button, select, textarea, [tabindex]");n&&n.focus()}function closeModal(e){const t=document.getElementById(e);t&&(t.style.display="none")}(function(){document.querySelectorAll("#pubLoginBtn, #footerLoginLink").forEach(e=>{e.addEventListener("click",t=>{t.preventDefault(),openModal("pubLoginModal"),ensureOptionsLoaded()})}),document.querySelectorAll("#pubRegBtn, #footerRegLink").forEach(e=>{e.addEventListener("click",t=>{t.preventDefault(),openModal("pubRegModal"),ensureOptionsLoaded(),ensureTerminiLoaded()})}),document.querySelectorAll(".pub-modal-close").forEach(e=>{e.addEventListener("click",()=>closeModal(e.dataset.modal))}),document.querySelectorAll(".pub-modal-overlay").forEach(e=>{e.addEventListener("click",t=>{t.target===e&&closeModal(e.id)})}),document.addEventListener("keydown",e=>{e.key==="Escape"&&document.querySelectorAll(".pub-modal-overlay").forEach(t=>{t.style.display="none"})})})(),(function(){const e=document.getElementById("pubSwitchToReg");e&&e.addEventListener("click",n=>{n.preventDefault(),openModal("pubRegModal"),ensureOptionsLoaded(),ensureTerminiLoaded()});const t=document.getElementById("pubSwitchToLogin");t&&t.addEventListener("click",n=>{n.preventDefault(),openModal("pubLoginModal")})})(),(function(){const e=document.getElementById("pubPwdRecoveryLink"),t=document.getElementById("pubPwdRecovery"),n=document.getElementById("pubPwdRecoveryForm"),o=document.getElementById("pubPwdMsg");!e||!t||!n||(e.addEventListener("click",c=>{c.preventDefault(),t.style.display=t.style.display==="none"?"block":"none"}),n.addEventListener("submit",async c=>{c.preventDefault();const i=document.getElementById("pubRecoveryEmail").value;try{const s=await(await fetch("/themes/crystal/home/recupero_password.php",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({email:i})})).json();o&&(o.textContent=s.response??"Email inviata.",o.style.display="block")}catch{o&&(o.textContent="Errore durante l'invio — riprova.",o.style.display="block")}}))})();let optionsLoaded=!1;function ensureOptionsLoaded(){optionsLoaded||fetch("/pages/api_iscrizione.php?op=options").then(e=>e.json()).then(e=>{if(!e.success)return;optionsLoaded=!0;const t=document.getElementById("pubRegRazza");t&&e.razze&&(t.innerHTML='<option value="0">Decidi poi</option>'+e.razze.map(o=>`<option value="${o.id}">${o.nome}</option>`).join(""));const n=document.getElementById("pubRegMestiere");n&&e.mestieri&&(n.innerHTML=e.mestieri.map(o=>`<option value="${o.id}">${o.nome}</option>`).join(""))}).catch(()=>{})}let terminiLoaded=!1;function ensureTerminiLoaded(){terminiLoaded||fetch("/pages/api_iscrizione.php?op=termini").then(e=>e.json()).then(e=>{if(!e.success)return;terminiLoaded=!0;const t=document.getElementById("pubInfoText");t&&e.regolamento&&(t.innerHTML=e.regolamento);const n=document.getElementById("pubTcText");n&&e.disclaimer&&(n.innerHTML=e.disclaimer)}).catch(()=>{})}(function(){function e(t,n){const o=document.getElementById(t),c=document.getElementById(n);!o||!c||o.addEventListener("click",()=>{const i=c.style.display!=="none";c.style.display=i?"none":"block",o.classList.toggle("open",!i)})}e("pubInfoToggle","pubInfoContent"),e("pubTcToggle","pubTcContent"),document.querySelectorAll(".faq-item").forEach(t=>{const n=t.querySelector(".faq-q"),o=t.querySelector(".faq-a");!n||!o||n.addEventListener("click",()=>{const c=o.style.display!=="none";document.querySelectorAll(".faq-a").forEach(i=>{i.style.display="none",i.closest(".faq-item")?.classList.remove("open")}),c||(o.style.display="block",t.classList.add("open"))})})})(),(function(){const e=document.getElementById("pubDoLogin"),t=document.getElementById("pubLoginError");e&&e.addEventListener("submit",async n=>{n.preventDefault(),t&&(t.style.display="none");const o=e.querySelector('[type="submit"]');o&&(o.disabled=!0);const c=document.getElementById("pubUsername")?.value??"",i=document.getElementById("pubPassword")?.value??"";try{const s=await(await fetch("/pages/api_auth.php?op=login",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({login:c,password:i})})).json();if(s.success){window.location.href=s.redirect;return}t&&(t.textContent=s.message||"Nome personaggio o password non riconosciuti.",t.style.display="block")}catch{t&&(t.textContent="Nome personaggio o password non riconosciuti.",t.style.display="block")}finally{o&&(o.disabled=!1)}})})(),(function(){
    const form = document.getElementById("pubRegForm");
    if (!form) return;
    const errorBox = document.getElementById("pubRegError");
    const successBox = document.getElementById("pubRegSuccess");
    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const tc = document.getElementById("pubRegTc");
        if (tc && !tc.checked) {
            alert("Devi accettare i Termini e Condizioni per procedere.");
            return;
        }
        if (errorBox) errorBox.style.display = "none";
        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;
        const payload = {
            email: document.getElementById("pubRegEmail")?.value ?? "",
            nome: document.getElementById("pubRegNome")?.value ?? "",
            cognome: document.getElementById("pubRegCognome")?.value ?? "",
            genere: form.querySelector('[name="genere"]')?.value ?? "m",
            razza: document.getElementById("pubRegRazza")?.value ?? "0",
            mestiere: document.getElementById("pubRegMestiere")?.value ?? "",
        };
        try {
            const res = await (await fetch("/pages/api_iscrizione.php?op=register", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify(payload),
            })).json();
            if (res.success) {
                form.style.display = "none";
                if (successBox) {
                    successBox.style.display = "block";
                    const setText = (id, value) => { const el = document.getElementById(id); if (el) el.textContent = value ?? ""; };
                    setText("pubRegSuccessNome", res.nome);
                    setText("pubRegSuccessMestiere", res.mestiere_nome);
                    setText("pubRegSuccessPassword", res.password);
                    const gildaRow = document.getElementById("pubRegSuccessGildaRow");
                    if (res.gilda_nome) {
                        setText("pubRegSuccessGilda", res.gilda_nome);
                        if (gildaRow) gildaRow.style.display = "";
                    } else if (gildaRow) {
                        gildaRow.style.display = "none";
                    }
                    const emailNote = document.getElementById("pubRegSuccessEmailNote");
                    if (emailNote) emailNote.style.display = res.emailconfirmation ? "block" : "none";
                }
                return;
            }
            if (errorBox) {
                errorBox.textContent = res.message || "Impossibile completare la registrazione.";
                errorBox.style.display = "block";
            }
        } catch {
            if (errorBox) {
                errorBox.textContent = "Errore di connessione — riprova.";
                errorBox.style.display = "block";
            }
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    });
})();
