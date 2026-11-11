/* =========================================================
   shop.js  -  mobile menu, toast, add-to-cart without reload
   Loaded (deferred) by navbar.php on every page
========================================================= */
(function () {
  "use strict";

  /* ---------- mobile drawer ---------- */
  var nav = document.getElementById("edNav"),
      burger = document.getElementById("edBurger"),
      overlay = document.getElementById("edOverlay"),
      closeBtn = document.getElementById("edClose");

  function setOpen(open) {
    if (!nav) return;
    nav.classList.toggle("open", open);
    if (overlay) overlay.classList.toggle("show", open);
    if (burger) burger.setAttribute("aria-expanded", open ? "true" : "false");
    document.body.classList.toggle("ed-lock", open);
  }
  if (burger)   burger.addEventListener("click", function () { setOpen(true); });
  if (closeBtn) closeBtn.addEventListener("click", function () { setOpen(false); });
  if (overlay)  overlay.addEventListener("click", function () { setOpen(false); });
  document.addEventListener("keydown", function (e) { if (e.key === "Escape") setOpen(false); });
  window.addEventListener("resize", function () { if (window.innerWidth > 900) setOpen(false); });

  /* sub-menu accordions (phones) */
  document.querySelectorAll(".ed-caret").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var item = btn.closest(".ed-item");
      var open = item.classList.toggle("open");
      btn.setAttribute("aria-expanded", open ? "true" : "false");
    });
  });

  /* ---------- toast ---------- */
  var toastEl = document.getElementById("edToast"), toastTimer;
  window.edToast = function (msg, type) {
    if (!toastEl) return;
    toastEl.textContent = msg;
    toastEl.className = "ed-toast show " + (type || "");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toastEl.classList.remove("show"); }, 2600);
  };

  function setCartCount(n) {
    document.querySelectorAll(".ed-cart-count").forEach(function (b) {
      b.textContent = n;
      b.hidden = !n;
    });
  }

  /* ---------- add to cart (forms marked data-ajax) ---------- */
  document.addEventListener("submit", function (ev) {
    var form = ev.target;
    if (!form.matches || !form.matches("form[data-ajax]")) return;

    var buyNow = form.querySelector('[name="buy_now"]');
    if (buyNow && buyNow.value === "1") return;          // "Buy now" = normal submit -> goes to cart

    ev.preventDefault();
    var btn = form.querySelector('button:not([type]), button[type="submit"]');
    if (btn) btn.disabled = true;

    fetch(form.action, {
      method: "POST",
      body: new FormData(form),
      credentials: "same-origin",
      headers: { "X-Requested-With": "fetch", "Accept": "application/json" }
    })
      .then(function (r) { return r.json().then(function (j) { return { status: r.status, data: j }; }); })
      .then(function (res) {
        var d = res.data || {};
        if (d.login) {                                    // not signed in -> send to login
          edToast(d.message || "Please log in first.", "err");
          setTimeout(function () { location.href = d.login; }, 900);
          return;
        }
        if (typeof d.cart_count === "number") setCartCount(d.cart_count);
        edToast(d.message || "Done", d.ok ? "ok" : "err");
      })
      .catch(function () { edToast("Network error. Please try again.", "err"); })
      .then(function () { if (btn) btn.disabled = false; });
  });
})();