// public/js/app.js — Consignation v2

document.addEventListener("DOMContentLoaded", () => {
  // ── Auto-dismiss flash messages ──────────────────────
  const flash = document.getElementById("flash-msg");
  if (flash) {
    setTimeout(() => {
      flash.style.transition = "opacity .5s";
      flash.style.opacity = "0";
      setTimeout(() => flash.remove(), 500);
    }, 4000);
  }

  // ── Demo account buttons (login page) ────────────────
  document.querySelectorAll(".demo-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const e = document.getElementById("email");
      const p = document.getElementById("password");
      if (e) e.value = btn.dataset.email;
      if (p) p.value = "password";
    });
  });

  // ── Fermer dropdown client si clic ailleurs ───────────
  document.addEventListener("click", (e) => {
    const box = document.getElementById("client-results");
    const inp = document.getElementById("client-search");
    if (box && inp && !inp.contains(e.target) && !box.contains(e.target)) {
      box.classList.add("hidden");
    }
  });

  // ── Keyboard : Escape ferme les modals ouverts ────────
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      document.querySelectorAll(".modal:not(.hidden)").forEach((m) => {
        m.classList.add("hidden");
      });
    }
  });

  // ── Cycle items animation (login page) ───────────────
  const items = document.querySelectorAll(".cycle-item");
  if (items.length) {
    let idx = 0;
    setInterval(() => {
      items.forEach((i) => i.classList.remove("cycle-item--accent"));
      items[idx].classList.add("cycle-item--accent");
      idx = (idx + 1) % items.length;
    }, 900);
  }
});
