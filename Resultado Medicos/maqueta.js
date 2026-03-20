document.addEventListener("DOMContentLoaded", function () {
  const markers = document.querySelectorAll(".js-marker");

  const preferReducedMotion =
    window.matchMedia &&
    window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  markers.forEach((marker, index) => {
    const pos = parseFloat(marker.dataset.pos) || 50;
    marker.style.left = "0%";

    if (preferReducedMotion) {
      marker.style.left = pos + "%";
      return;
    }

    setTimeout(() => {
      marker.style.left = pos + "%";
    }, 200 + index * 180);
  });

  // Modal centrado para historia (hover / focus)
  const historiaVisuals = document.querySelectorAll(".historia-visual");

  historiaVisuals.forEach((visual) => {
    const overlay = visual.querySelector(".historia-modal-overlay");
    const closeBtn = visual.querySelector(".historia-modal-close");
    if (!overlay) return;

    let closeTimer = null;
    let pointerOverOverlay = false;

    const open = () => {
      if (overlay.classList.contains("is-open")) return;
      overlay.classList.add("is-open");
      overlay.setAttribute("aria-hidden", "false");
    };

    const close = () => {
      overlay.classList.remove("is-open");
      overlay.setAttribute("aria-hidden", "true");
    };

    const startCloseDelay = () => {
      closeTimer = window.setTimeout(() => {
        // Solo cerramos si el mouse ya no está sobre la superposición
        if (!pointerOverOverlay) close();
        closeTimer = null;
      }, 120);
    };

    const cancelCloseDelay = () => {
      if (closeTimer) {
        window.clearTimeout(closeTimer);
        closeTimer = null;
      }
    };

    visual.addEventListener("mouseenter", open);
    visual.addEventListener("mouseleave", startCloseDelay);
    visual.addEventListener("focusin", open);
    visual.addEventListener("focusout", startCloseDelay);

    overlay.addEventListener("mouseenter", () => {
      pointerOverOverlay = true;
      cancelCloseDelay();
    });
    overlay.addEventListener("mouseleave", () => {
      pointerOverOverlay = false;
      startCloseDelay();
    });

    overlay.addEventListener("click", (e) => {
      // Cerrar solo al hacer click en el backdrop, no en el contenido
      if (e.target === overlay) close();
    });

    if (closeBtn) {
      closeBtn.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        close();
      });
    }
  });

  // Cerrar con ESC
  document.addEventListener("keydown", (e) => {
    if (e.key !== "Escape") return;
    document
      .querySelectorAll(".historia-modal-overlay.is-open")
      .forEach((ov) => {
        ov.classList.remove("is-open");
        ov.setAttribute("aria-hidden", "true");
      });
  });
});

