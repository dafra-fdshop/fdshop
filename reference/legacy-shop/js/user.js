// ==========================================
// FD VM Carousel: Rebuild Bootstrap slides per breakpoint
// Fix: "fehlende Produkte" bei < 5 Spalten
// ==========================================
(function () {
  "use strict";

  // Breakpoints passend zu deinem CSS:
  // 5: default, 4: <1400, 3: <1200, 2: <992, 1: <768
  function getColsPerSlide() {
    const w = window.innerWidth;
    if (w < 768) return 1;
    if (w < 992) return 2;
    if (w < 1200) return 3;
    if (w < 1400) return 4;
    return 5;
  }

  function chunk(arr, size) {
    const out = [];
    for (let i = 0; i < arr.length; i += size) out.push(arr.slice(i, i + size));
    return out;
  }

  function rebuildCarousel(carouselEl) {
    const inner = carouselEl.querySelector(".carousel-inner");
    if (!inner) return;

    // Alle Produkt-Karten einsammeln (egal aus welchem Slide)
    const allCols = Array.from(inner.querySelectorAll(".fd-vmcarousel-col"));
    if (!allCols.length) return;

    // Aktiven Slide bestimmen, damit wir ungefähr die Position halten
    const activeItem = inner.querySelector(".carousel-item.active");
    const itemsBeforeActive = activeItem
      ? activeItem.querySelectorAll(".fd-vmcarousel-col").length *
        Array.from(inner.children).indexOf(activeItem)
      : 0;

    const colsPerSlide = getColsPerSlide();

    // Wenn sich nichts ändert: abbrechen (wichtig für Performance)
    const currentFirstItem = inner.querySelector(".carousel-item");
    const currentColsInFirst =
      currentFirstItem?.querySelectorAll(".fd-vmcarousel-col").length || 0;
    if (currentColsInFirst === colsPerSlide) return;

    // Bootstrap-Instanz sauber entsorgen (sonst spinnt der State)
    const existing = window.bootstrap?.Carousel?.getInstance(carouselEl);
    if (existing) existing.dispose();

    // Inner leeren und neu aufbauen
    inner.innerHTML = "";

    const slides = chunk(allCols, colsPerSlide);

    slides.forEach((cols, idx) => {
      const item = document.createElement("div");
      item.className = "carousel-item";

      const track = document.createElement("div");
      track.className = "fd-vmcarousel-track";

      cols.forEach((col) => track.appendChild(col)); // DOM-Nodes verschieben (keine Duplikate)
      item.appendChild(track);
      inner.appendChild(item);

      // Active: grob anhand der bisherigen Position
      // (erste sichtbare Karte bleibt ungefähr gleich)
      const startIndex = idx * colsPerSlide;
      if (startIndex <= itemsBeforeActive && itemsBeforeActive < startIndex + colsPerSlide) {
        item.classList.add("active");
      }
    });

    // Falls aus irgendeinem Grund kein active gesetzt wurde
    if (!inner.querySelector(".carousel-item.active")) {
      const first = inner.querySelector(".carousel-item");
      if (first) first.classList.add("active");
    }

    // Controls ein/aus (dein Wrapper existiert evtl. immer)
    const controls = carouselEl.querySelector(".fd-vmcarousel-controls");
    if (controls) controls.style.display = slides.length > 1 ? "" : "none";

    // Carousel neu initialisieren (deine Settings)
    if (window.bootstrap?.Carousel) {
      new window.bootstrap.Carousel(carouselEl, {
        interval: false,
        touch: true
      });
    }
  }

  // Debounce für resize/orientationchange
  function debounce(fn, wait) {
    let t;
    return function () {
      clearTimeout(t);
      t = setTimeout(fn, wait);
    };
  }

  function init() {
    document.querySelectorAll(".fd-vmcarousel.carousel").forEach((carouselEl) => {
      rebuildCarousel(carouselEl);

      const onResize = debounce(() => rebuildCarousel(carouselEl), 150);
      window.addEventListener("resize", onResize, { passive: true });
      window.addEventListener("orientationchange", onResize, { passive: true });
    });
  }

  document.addEventListener("DOMContentLoaded", init);
})();


// ==========================================
// Video Modal Handling (Bootstrap) + Focus-Fix (X/ESC/Backdrop)
// ==========================================
document.addEventListener('DOMContentLoaded', function () {
  const modalEl = document.getElementById('fdModal');
  if (!modalEl) return;

  const frame = document.getElementById('fdModalFrame');
  let lastTrigger = null;

  // Öffnen: Trigger merken + Video setzen
  modalEl.addEventListener('show.bs.modal', function (event) {
    lastTrigger = event.relatedTarget || null;

    const src = lastTrigger?.getAttribute('data-video-src') || '';
    if (frame) frame.src = src || 'about:blank';
  });

  // SCHLÜSSEL: beim Start des Schließens Focus-Trap deaktivieren und Fokus zurück
  modalEl.addEventListener('hide.bs.modal', function () {
    try {
      const instance = bootstrap.Modal.getInstance(modalEl);
      // Bootstrap 5: FocusTrap ist intern -> wir nutzen ihn bewusst hier
      if (instance && instance._focustrap && typeof instance._focustrap.deactivate === 'function') {
        instance._focustrap.deactivate();
      }
    } catch (e) {
      // egal, dann ohne Trap-Deaktivierung weiter
    }

    // Fokus raus aus dem Modal
    if (lastTrigger && typeof lastTrigger.focus === 'function') {
      lastTrigger.focus();
    } else {
      document.body.focus?.();
    }
  });

  // Nach dem Schließen: Video stoppen
  modalEl.addEventListener('hidden.bs.modal', function () {
    if (frame) frame.src = 'about:blank';
  });
});

//=======================================
// VM Cart Module: Counter + Discount refresh (slim)
//=======================================
document.addEventListener("DOMContentLoaded", () => {
  const modules = document.querySelectorAll(".vmCartModule[data-module-id]");
  if (!modules.length) return;

  const parseEuro = (txt) => {
    // "Summe 9,81 €" -> 9.81
    const s = (txt || "")
      .replace(/\.(?=\d{3}(\D|$))/g, "") // 1.234,56 -> 1234,56
      .replace(",", ".")
      .replace(/[^0-9.\-]/g, "");
    const n = parseFloat(s);
    return Number.isFinite(n) ? n : NaN;
  };

  const trunc2 = (x) => Math.floor(x * 100 + 1e-6) / 100;

  modules.forEach((mod) => {
    const btnCounter   = mod.querySelector(".button-wrapper .products-number");
    const productsWrap = mod.querySelector(".vm_cart_products");
    if (!btnCounter || !productsWrap) return;

    const totalEl        = mod.querySelector(".fd-cart-summary .total");
    const discountWrap   = mod.querySelector(".fd-cart-summary-top");
    const discountValue  = discountWrap?.querySelector(".fd-cart-discount-value");

    let scheduled = false;

    const sync = () => {
      if (scheduled) return;
      scheduled = true;

      requestAnimationFrame(() => {
        scheduled = false;

        // Counter
        let sum = 0;
        mod.querySelectorAll(".vm_cart_products .product_row .quantity").forEach((q) => {
          const n = parseInt((q.textContent || "").trim(), 10);
          if (!Number.isNaN(n)) sum += n;
        });
        btnCounter.textContent = String(sum);

        // Discount (nur wenn Markup existiert)
        if (!totalEl || !discountWrap || !discountValue) return;

        const finalTotal = parseEuro(totalEl.textContent);

        // leer / nicht berechenbar
        if (!Number.isFinite(finalTotal) || finalTotal <= 0) {
          discountValue.textContent = "";
          discountWrap.hidden = true;
          return;
        }

        // 10% rückwärts, dann abschneiden (wie PHP)
        const original = finalTotal / 0.9;
        const diff = trunc2(original - finalTotal);

        // WICHTIG: hidden passend togglen
        discountWrap.hidden = !(diff > 0);

        // Text bauen
        const hasEuro = /€/.test(totalEl.textContent || "");
        const euro = hasEuro ? " €" : "";
        const text = diff > 0 ? `-${diff.toFixed(2).replace(".", ",")}${euro}` : "";

        if (discountValue.textContent !== text) discountValue.textContent = text;
      });
    };

    sync();

    const mo = new MutationObserver(sync);
    mo.observe(productsWrap, { childList: true, subtree: true });

    if (totalEl) {
      mo.observe(totalEl, { childList: true, subtree: true, characterData: true });
    }
  });
});



	





