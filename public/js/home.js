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
    const cityInput = document.querySelector("[data-home-city]");
    const provinceInput = document.querySelector("[data-home-province]");
    const zoomInput = document.querySelector("[data-home-zoom]");
    const useLocationButton = document.querySelector("[data-home-use-location]");
    const locationStatus = document.querySelector("[data-home-location-status]");
    let locationAutocomplete = null;
    let autocompleteMode = "none";
    let autocompletePanel = null;
    let autocompleteSessionToken = null;
    let autocompleteRequestTimeout = null;
    let latestAutocompleteRequestId = 0;

    const setStatus = (message, isError = false) => {
        if (!locationStatus) return;

        locationStatus.textContent = message;
        locationStatus.classList.toggle("is-error", isError);
    };

    const applyCoordinates = (latitude, longitude) => {
        const parsedLatitude = Number(latitude);
        const parsedLongitude = Number(longitude);

        if (!Number.isFinite(parsedLatitude) || !Number.isFinite(parsedLongitude)) return false;

        if (latitudeInput) latitudeInput.value = String(parsedLatitude);
        if (longitudeInput) longitudeInput.value = String(parsedLongitude);

        return true;
    };

    const addressComponent = (components, ...types) => {
        const component = components?.find((item) => types.some((type) => item.types.includes(type)));
        return component?.long_name || component?.longText || component?.shortText || "";
    };

    const clearResolvedLocation = () => {
        if (latitudeInput) latitudeInput.value = "";
        if (longitudeInput) longitudeInput.value = "";
        if (cityInput) cityInput.value = "";
        if (provinceInput) provinceInput.value = "";
        searchForm?.removeAttribute("data-geocoded");
    };

    const applyGoogleResult = (result, fallbackLabel = "") => {
        const position = result?.geometry?.location;
        if (!position || !applyCoordinates(position.lat(), position.lng())) return false;

        const components = result.address_components || [];
        const city = addressComponent(components, "locality", "postal_town", "administrative_area_level_3");
        const province = addressComponent(components, "administrative_area_level_2", "administrative_area_level_1");
        const label = result.formatted_address || result.name || fallbackLabel;

        if (locationInput && label) locationInput.value = label;
        if (cityInput) cityInput.value = city;
        if (provinceInput) provinceInput.value = province;
        if (zoomInput) zoomInput.value = "13";

        return true;
    };

    const applyModernPlace = (place) => {
        const latitude = typeof place?.location?.lat === "function" ? place.location.lat() : Number(place?.location?.lat);
        const longitude = typeof place?.location?.lng === "function" ? place.location.lng() : Number(place?.location?.lng);
        if (!applyCoordinates(latitude, longitude)) return false;

        const components = place.addressComponents || [];
        if (locationInput) locationInput.value = place.formattedAddress || place.displayName || locationInput.value;
        if (cityInput) cityInput.value = addressComponent(components, "locality", "postal_town", "administrative_area_level_3");
        if (provinceInput) provinceInput.value = addressComponent(components, "administrative_area_level_2", "administrative_area_level_1");
        if (zoomInput) zoomInput.value = "13";

        return true;
    };

    const hideAutocompletePanel = () => {
        latestAutocompleteRequestId += 1;
        autocompletePanel?.classList.add("is-hidden");
        autocompletePanel?.replaceChildren();
    };

    const createAutocompletePanel = () => {
        if (!locationInput || autocompletePanel) return;

        autocompletePanel = document.createElement("div");
        autocompletePanel.className = "home-google-autocomplete is-hidden";
        autocompletePanel.setAttribute("role", "listbox");
        locationInput.parentElement?.appendChild(autocompletePanel);
    };

    const selectModernSuggestion = async (suggestion) => {
        hideAutocompletePanel();
        const placePrediction = suggestion?.placePrediction;
        if (!placePrediction) return;

        try {
            const place = placePrediction.toPlace();
            await place.fetchFields({
                fields: ["addressComponents", "displayName", "formattedAddress", "location"],
            });

            if (!applyModernPlace(place)) throw new Error("Place location is unavailable");

            autocompleteSessionToken = null;
            searchForm.dataset.geocoded = "true";
            setStatus(`Ubicación aplicada: ${locationInput.value}.`);
        } catch (error) {
            clearResolvedLocation();
            setStatus("No se pudo validar la dirección seleccionada.", true);
            console.error("Google Places selection error:", error);
        }
    };

    const renderModernSuggestions = (suggestions) => {
        if (!autocompletePanel) return;
        autocompletePanel.replaceChildren();

        const usableSuggestions = (suggestions || []).filter((suggestion) => suggestion?.placePrediction).slice(0, 6);
        if (!usableSuggestions.length) {
            hideAutocompletePanel();
            return;
        }

        const brand = document.createElement("span");
        brand.className = "home-google-autocomplete__brand";
        brand.textContent = "Sugerencias de Google";
        autocompletePanel.appendChild(brand);

        usableSuggestions.forEach((suggestion) => {
            const button = document.createElement("button");
            button.type = "button";
            button.className = "home-google-autocomplete__option";
            button.setAttribute("role", "option");
            button.textContent = suggestion.placePrediction.text?.toString() || "";
            button.addEventListener("mousedown", (event) => event.preventDefault());
            button.addEventListener("click", () => selectModernSuggestion(suggestion));
            autocompletePanel.appendChild(button);
        });

        autocompletePanel.classList.remove("is-hidden");
    };

    const fetchModernSuggestions = async (query) => {
        const trimmedQuery = query.trim();
        if (trimmedQuery.length < 3) {
            hideAutocompletePanel();
            return;
        }

        const placesLibrary = await window.google.maps.importLibrary("places");
        const requestId = ++latestAutocompleteRequestId;
        if (!autocompleteSessionToken && placesLibrary.AutocompleteSessionToken) {
            autocompleteSessionToken = new placesLibrary.AutocompleteSessionToken();
        }

        try {
            const request = {
                input: trimmedQuery,
                includedRegionCodes: ["es"],
                language: "es",
                region: "es",
            };
            if (autocompleteSessionToken) request.sessionToken = autocompleteSessionToken;

            const response = await placesLibrary.AutocompleteSuggestion.fetchAutocompleteSuggestions(request);
            if (requestId === latestAutocompleteRequestId) renderModernSuggestions(response?.suggestions || []);
        } catch (error) {
            if (requestId === latestAutocompleteRequestId) hideAutocompletePanel();
            console.error("Google Places autocomplete error:", error);
        }
    };

    const queueModernSuggestions = (query) => {
        if (autocompleteRequestTimeout) window.clearTimeout(autocompleteRequestTimeout);
        autocompleteRequestTimeout = window.setTimeout(() => fetchModernSuggestions(query), 250);
    };

    const initLocationAutocomplete = async (attempt = 0) => {
        if (!locationInput || locationAutocomplete) return;

        if (!window.google?.maps) {
            if (attempt < 40) window.setTimeout(() => initLocationAutocomplete(attempt + 1), 250);
            return;
        }

        if (window.google.maps.importLibrary) {
            const placesLibrary = await window.google.maps.importLibrary("places").catch(() => null);
            if (placesLibrary?.AutocompleteSuggestion) {
                autocompleteMode = "modern";
                locationAutocomplete = { modern: true };
                createAutocompletePanel();
                return;
            }
        }

        if (!window.google.maps.places?.Autocomplete) return;

        locationAutocomplete = new window.google.maps.places.Autocomplete(locationInput, {
            componentRestrictions: { country: "es" },
            fields: ["address_components", "formatted_address", "geometry", "name"],
            types: ["geocode"],
        });

        locationAutocomplete.addListener("place_changed", () => {
            const place = locationAutocomplete.getPlace();
            if (!applyGoogleResult(place, locationInput.value)) {
                setStatus("Selecciona una dirección de las sugerencias de Google Maps.", true);
                return;
            }

            searchForm.dataset.geocoded = "true";
            setStatus(`Ubicación aplicada: ${locationInput.value}.`);
        });
    };

    const geocodeManualLocation = (callback) => {
        const query = String(locationInput?.value || "").trim();

        if (!query || !window.google?.maps?.Geocoder) {
            callback?.(false);
            return;
        }

        let settled = false;
        const finish = (resolved) => {
            if (settled) return;
            settled = true;
            window.clearTimeout(timeoutId);
            callback?.(resolved);
        };
        const timeoutId = window.setTimeout(() => {
            setStatus("Google no respondió. Buscaremos usando el texto introducido.", true);
            finish(false);
        }, 6000);
        const geocoder = new window.google.maps.Geocoder();
        geocoder.geocode({ address: query, componentRestrictions: { country: "ES" } }, (results, status) => {
            if (status !== "OK" || !results?.[0]) {
                setStatus("No hemos podido localizar esa zona. Buscaremos usando el texto introducido.", true);
                finish(false);
                return;
            }

            const applied = applyGoogleResult(results[0], query);
            setStatus(
                applied ? `Ubicación aplicada: ${locationInput.value}.` : "No hemos podido obtener las coordenadas de esa zona.",
                !applied
            );
            finish(applied);
        });
    };

    const reverseGeocodeCoordinates = (latitude, longitude, callback) => {
        if (!window.google?.maps?.Geocoder) {
            callback?.(false);
            return;
        }

        let settled = false;
        const finish = (resolved) => {
            if (settled) return;
            settled = true;
            window.clearTimeout(timeoutId);
            callback?.(resolved);
        };
        const timeoutId = window.setTimeout(() => finish(false), 6000);
        const geocoder = new window.google.maps.Geocoder();
        geocoder.geocode({ location: { lat: latitude, lng: longitude } }, (results, status) => {
            const applied = status === "OK" && results?.[0] ? applyGoogleResult(results[0]) : false;
            finish(applied);
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

    locationInput?.addEventListener("input", () => {
        clearResolvedLocation();
        if (autocompleteMode === "modern") queueModernSuggestions(locationInput.value);
    });
    locationInput?.addEventListener("focus", () => {
        if (autocompleteMode === "modern" && autocompletePanel?.childElementCount) {
            autocompletePanel.classList.remove("is-hidden");
        }
    });
    locationInput?.addEventListener("blur", () => {
        if (autocompleteMode === "modern") window.setTimeout(hideAutocompletePanel, 150);
    });
    locationInput?.addEventListener("keydown", (event) => {
        if (autocompleteMode !== "modern" || event.key !== "Enter" || autocompletePanel?.classList.contains("is-hidden")) return;
        const firstOption = autocompletePanel?.querySelector(".home-google-autocomplete__option");
        if (!firstOption) return;
        event.preventDefault();
        firstOption.click();
    });
    initLocationAutocomplete();

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
                const latitude = Number(position.coords.latitude);
                const longitude = Number(position.coords.longitude);
                applyCoordinates(latitude, longitude);
                reverseGeocodeCoordinates(latitude, longitude, (resolved) => {
                    setStatus(resolved
                        ? `Ubicación detectada: ${locationInput.value}.`
                        : "Ubicación autorizada. Ya puedes buscar profesionales cerca de ti.");
                    useLocationButton.disabled = false;
                });
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
        if (searchForm.dataset.geocoded === "true") return;

        const manualLocation = String(locationInput?.value || "").trim();
        if (!manualLocation || latitudeInput?.value || !window.google?.maps?.Geocoder) return;

        event.preventDefault();
        geocodeManualLocation(() => {
            searchForm.dataset.geocoded = "true";
            searchForm.submit();
        });
    });
})();
