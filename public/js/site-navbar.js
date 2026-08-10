(() => {
    "use strict";

    const menuButton = document.querySelector("[data-home-menu-button]");
    const disclosure = document.querySelector("[data-home-navigation-disclosure]");
    const navigation = document.querySelector("[data-home-navigation]");
    const announcement = document.querySelector(".home-announcement");
    const announcementClose = document.querySelector("[data-home-announcement-close]");

    disclosure?.addEventListener("toggle", () => {
        menuButton?.setAttribute("aria-label", disclosure.open ? "Cerrar menú" : "Abrir menú");
    });

    navigation?.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", () => {
            disclosure?.removeAttribute("open");
            menuButton?.setAttribute("aria-label", "Abrir menú");
        });
    });

    announcementClose?.addEventListener("click", () => {
        announcement.hidden = true;
    });
})();
