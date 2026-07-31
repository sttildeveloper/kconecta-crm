(() => {
    "use strict";

    const menuButton = document.querySelector("[data-home-menu-button]");
    const navigation = document.querySelector("[data-home-navigation]");
    const announcement = document.querySelector(".home-announcement");
    const announcementClose = document.querySelector("[data-home-announcement-close]");
    const searchForm = document.querySelector("[data-home-search]");
    const locationInput = document.querySelector("[data-home-location]");
    const latitudeInput = document.querySelector("[data-home-latitude]");
    const longitudeInput = document.querySelector("[data-home-longitude]");
    const useLocationButton = document.querySelector("[data-home-use-location]");
    const locationStatus = document.querySelector("[data-home-location-status]");

    const setStatus = (message, isError = false) => {
        if (!locationStatus) {
            return;
        }

        locationStatus.textContent = message;
        locationStatus.classList.toggle("is-error", isError);
    };

    const applyCoordinates = (latitude, longitude, label = "") => {
        const parsedLatitude = Number(latitude);
        const parsedLongitude = Number(longitude);

        if (!Number.isFinite(parsedLatitude) || !Number.isFinite(parsedLongitude)) {
            return false;
        }

        if (latitudeInput) latitudeInput.value = String(parsedLatitude);
        if (longitudeInput) longitudeInput.value = String(parsedLongitude);
        if (locationInput && label) locationInput.value = label;

        return true;
    };

    const geocodeManualLocation = (callback) => {
        const query = String(locationInput?.value || "").trim();

        if (!query || !window.google?.maps?.Geocoder) {
            callback?.(false);
            return;
        }

        const geocoder = new window.google.maps.Geocoder();
        geocoder.geocode({ address: query, componentRestrictions: { country: "ES" } }, (results, status) => {
            if (status !== "OK" || !results?.[0]) {
                setStatus("No hemos podido localizar esa zona. Buscaremos usando el texto introducido.", true);
                callback?.(false);
                return;
            }

            const result = results[0];
            const position = result.geometry.location;
            const locality = result.address_components?.find((component) =>
                component.types.includes("locality") || component.types.includes("postal_town")
            )?.long_name;

            applyCoordinates(position.lat(), position.lng(), locality || query);
            setStatus(`Ubicación aplicada: ${locality || query}.`);
            callback?.(true);
        });
    };

    menuButton?.addEventListener("click", () => {
        const isOpen = navigation?.classList.toggle("is-open") || false;
        menuButton.setAttribute("aria-expanded", String(isOpen));
        menuButton.setAttribute("aria-label", isOpen ? "Cerrar menú" : "Abrir menú");
    });

    navigation?.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", () => {
            navigation.classList.remove("is-open");
            menuButton?.setAttribute("aria-expanded", "false");
            menuButton?.setAttribute("aria-label", "Abrir menú");
        });
    });

    announcementClose?.addEventListener("click", () => {
        announcement.hidden = true;
    });

    useLocationButton?.addEventListener("click", () => {
        if (!navigator.geolocation) {
            setStatus("Tu navegador no permite obtener la ubicación. Introduce una ciudad o código postal.", true);
            locationInput?.focus();
            return;
        }

        useLocationButton.disabled = true;
        setStatus("Obteniendo tu ubicación…");

        navigator.geolocation.getCurrentPosition(
            (position) => {
                applyCoordinates(position.coords.latitude, position.coords.longitude);
                setStatus("Ubicación autorizada. Ya puedes buscar profesionales cerca de ti.");
                useLocationButton.disabled = false;
            },
            () => {
                setStatus("No hemos podido usar tu ubicación. Introduce una ciudad o código postal.", true);
                useLocationButton.disabled = false;
                locationInput?.focus();
            },
            { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 }
        );
    });

    searchForm?.addEventListener("submit", (event) => {
        if (searchForm.dataset.geocoded === "true") {
            return;
        }

        const manualLocation = String(locationInput?.value || "").trim();
        if (!manualLocation || latitudeInput?.value || !window.google?.maps?.Geocoder) {
            return;
        }

        event.preventDefault();
        geocodeManualLocation(() => {
            searchForm.dataset.geocoded = "true";
            searchForm.submit();
        });
    });
})();
