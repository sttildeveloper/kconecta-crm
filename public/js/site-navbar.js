(() => {
    "use strict";

    const menuButton = document.querySelector("[data-home-menu-button]");
    const disclosure = document.querySelector("[data-home-navigation-disclosure]");
    const navigation = document.querySelector("[data-home-navigation]");
    const announcement = document.querySelector(".home-announcement");
    const announcementClose = document.querySelector("[data-home-announcement-close]");
    const setMenuOpen = (isOpen) => {
        disclosure?.classList.toggle("is-open", isOpen);
        menuButton?.setAttribute("aria-expanded", String(isOpen));
        menuButton?.setAttribute("aria-label", isOpen ? "Cerrar menú" : "Abrir menú");
    };

    menuButton?.addEventListener("click", () => {
        setMenuOpen(!disclosure?.classList.contains("is-open"));
    });

    navigation?.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", () => {
            setMenuOpen(false);
        });
    });

    window.matchMedia("(min-width: 1021px)").addEventListener("change", () => {
        setMenuOpen(false);
    });

    announcementClose?.addEventListener("click", () => {
        announcement.hidden = true;
    });
})();
