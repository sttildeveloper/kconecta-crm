@extends('layouts.page')

@section('nav_option')
<a href="<?= site_url() ?>">
    <span>Ir al inicio</span>
</a>
@endsection

@section('css')
    <link rel="stylesheet" href="<?= base_url()."css/page/details.css" ?>">
    <link rel="stylesheet" href="<?= base_url()."css/page/result_all.css" ?>">
    <link rel="stylesheet" href="<?= base_url()."css/page/result_all_services.css" ?>">
    <link rel="stylesheet" href="<?= base_url()."css/ui/input_number_cont.css" ?>">
    <link rel="stylesheet" href="<?= base_url()."css/ui/input_radio.css" ?>">
    <link rel="stylesheet" href="<?= base_url()."css/ui/input_checkbox.css" ?>">

    <script>
        window.__gmAuthFailed = false;
        window.__gmAuthFailureCallback = null;
        window.gm_authFailure = function () {
            window.__gmAuthFailed = true;
            if (typeof window.__gmAuthFailureCallback === "function") {
                window.__gmAuthFailureCallback();
            }
        };
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= config('services.google.maps_key') ?>&libraries=drawing,places" referrerpolicy="strict-origin-when-cross-origin"></script>
@endsection

@section('content')

@php
    $normalizedSearchAddress = trim(implode(', ', array_filter([
        !empty($city) ? trim((string) $city) : null,
        !empty($province) ? trim((string) $province) : null,
    ])));

    $searchAddressValue = $normalizedSearchAddress !== '' ? $normalizedSearchAddress : (!empty($address) ? trim((string) $address) : '');
    $selectedSpecialties = [];

    if (!empty($sti)) {
        foreach ($service_type as $serviceTypeItem) {
            if (in_array($serviceTypeItem['id'], $sti)) {
                $selectedSpecialties[] = $serviceTypeItem['name'];
            }
        }
    }
@endphp

<div class="container-result-all providers-results-page">
    <div class="results-top-shell">
        <section class="results-hero-card">
            <div class="results-hero-copy">
                <span class="results-hero-kicker">Búsqueda de proveedores</span>
                <h1>Encuentra profesionales en toda España</h1>
                <p>Compara proveedores verificados y localiza especialistas por zona desde el mapa o la lista.</p>
            </div>
            <div class="results-hero-illustration" aria-hidden="true">
                <div class="results-hero-illustration__skyline"></div>
            </div>
        </section>

        <div class="results-search-card">
            <form action="" method="get" id="form-filter-result">
                <input type="hidden" name="page" value="1" id="input-page-nav">
                <input type="hidden" name="mode" id="mode" value="<?= $mode ?>">
                <input type="hidden" name="city" id="city" value="<?= $city ?>">
                <input type="hidden" name="province" id="province" value="<?= $province ?>">
                <input type="hidden" name="latitude" id="latitude" value="<?= $latitude ?>">
                <input type="hidden" name="longitude" id="longitude" value="<?= $longitude ?>">
                <input type="hidden" name="zoom" id="zoom" value="<?= $zoom ?>">

                <div class="results-search-grid">
                    <label class="results-field results-field--location">
                        <span class="results-field__label">Localidad</span>
                        <span class="results-field__control">
                            <span class="results-field__icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2c-4.4 0-8 3.6-8 8c0 5.4 7 11.5 7.3 11.8c.2.1.5.2.7.2s.5-.1.7-.2C13 21.5 20 15.4 20 10c0-4.4-3.6-8-8-8m0 17.7c-2.1-2-6-6.3-6-9.7c0-3.3 2.7-6 6-6s6 2.7 6 6s-3.9 7.7-6 9.7M12 6c-2.2 0-4 1.8-4 4s1.8 4 4 4s4-1.8 4-4s-1.8-4-4-4m0 6c-1.1 0-2-.9-2-2s.9-2 2-2s2 .9 2 2s-.9 2-2 2"/></svg>
                            </span>
                            <input type="text" name="address" id="address" value="{{ $searchAddressValue }}" class="input" placeholder="Buscar localidad en España" autocomplete="off">
                        </span>
                        <span class="results-field__hint">Ciudad, distrito, código postal o provincia</span>
                    </label>

                    <div class="results-field results-field--services">
                        <span class="results-field__label">Servicios</span>
                        <div class="results-services-summary">
                            @if (!empty($selectedSpecialties))
                                <div class="results-services-summary__content">
                                    @foreach ($selectedSpecialties as $specialtyName)
                                        <span class="results-service-chip">{{ $specialtyName }}</span>
                                    @endforeach
                                </div>
                            @else
                                <div class="results-services-summary__empty">
                                    <span class="results-services-summary__empty-title">Sin especialidades seleccionadas</span>
                                    <span class="results-services-placeholder">Selecciona una o varias especialidades desde el panel lateral.</span>
                                </div>
                            @endif
                        </div>
                        <span class="results-field__hint">{{ !empty($selectedSpecialties) ? count($selectedSpecialties).' categorías seleccionadas' : 'Afina la búsqueda por especialidad' }}</span>
                    </div>

                    <div class="results-actions">
                        <button class="button save-search-main" type="submit">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 16 16"><path fill="currentColor" d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0a5.5 5.5 0 0 1 11 0"/></svg>
                            Buscar
                        </button>
                        <button class="button clear-search-main" type="button" id="btn-clear-service-filters">
                            Limpiar filtros
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="container-properties">
        <aside class="container-title-section">
            <button class="btn-close" type="button" aria-label="Cerrar filtros"><svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18M6 6l12 12"/></svg></button>
            <div class="container-details-header-get">
                <span class="results-aside-kicker">Categorías de servicio</span>
                <?php
                    if ($quantity === 0) {
                        echo "<h2>Sin resultados</h2>";
                    } else if ($quantity === 1) {
                        echo "<h2>1 proveedor encontrado</h2>";
                    } else {
                        echo "<h2>".$quantity." proveedores encontrados</h2>";
                    }
                ?>
                <p><?= !empty($address) ? $address : "Explora todas las zonas disponibles y afina por especialidad." ?></p>
            </div>

            <div class="container-filter-result">
                <div class="container-form-inputs">
                    <span>Especialidades</span>
                    <div class="container-label-radio">
                        <?php foreach($service_type as $st){ ?>
                            <label class="radio label-radio-checkbox-col-100">
                                <input type="checkbox" class="checkbox-input-ui" hidden="" name="sti[]" value="<?= $st["id"]  ?>" form="form-filter-result" <?php if (!empty($sti)){if (in_array($st["id"], $sti)){ echo "checked";}} ?>>
                                <span class="checkmark-checkbox-input-ui"></span>
                                <span class="results-filter-name"><?= $st["name"] ?></span>
                            </label>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </aside>

        <div class="container-body-data-result" id="container-body-data-result">
            <div class="results-toolbar">
                <div class="results-toolbar__summary">
                    <button class="button is-small btn-open" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 512 512"><path fill="currentColor" d="M238.627 496H192V253.828l-168-200V16h456v37.612l-160 200v161.015ZM224 464h1.373L288 401.373V242.388L443.51 48H60.9L224 242.172Z"/></svg>
                        <span>Filtros</span>
                    </button>
                    <div>
                        <span class="results-toolbar__eyebrow">Resultado actual</span>
                        <strong><?= $quantity ?> coincidencias<?= !empty($address) ? " en ".$address : "" ?></strong>
                    </div>
                </div>

                <div class="container-nav-search-body tabs is-right">
                    <ul>
                        <li class="<?= $mode == 1 ? "is-active" : "" ?>">
                            <button class="button-change-page-result" data-mode="1" type="button">
                                <span class="icon is-small"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.5 12c0-4.478 0-6.718 1.391-8.109S7.521 2.5 12.001 2.5c4.478 0 6.717 0 8.108 1.391S21.5 7.521 21.5 12c0 4.478 0 6.718-1.391 8.109S16.479 21.5 12 21.5c-4.478 0-6.717 0-8.109-1.391c-1.39-1.392-1.39-3.63-1.39-8.109M2.5 8h19M11 17h6M7 17h1m3-4h6M7 13h1"/></svg></span>
                                <span>Ver lista</span>
                            </button>
                        </li>
                        <li class="<?= $mode != 1 ? "is-active" : "" ?>">
                            <button class="button-change-page-result" data-mode="2" type="button">
                                <span class="icon is-small"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M15.129 13.747a.906.906 0 0 1-1.258 0c-1.544-1.497-3.613-3.168-2.604-5.595A3.53 3.53 0 0 1 14.5 6c1.378 0 2.688.84 3.233 2.152c1.008 2.424-1.056 4.104-2.604 5.595M14.5 9.5h.009"/><path d="M2.5 12c0-4.478 0-6.718 1.391-8.109S7.521 2.5 12 2.5c4.478 0 6.718 0 8.109 1.391S21.5 7.521 21.5 12c0 4.478 0 6.718-1.391 8.109S16.479 21.5 12 21.5c-4.478 0-6.718 0-8.109-1.391S2.5 16.479 2.5 12M17 21L3 7m7 7l-6 6"/></g></svg></span>
                                <span>Ver mapa</span>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <?php if ($mode == 1){ ?>
                <div class="card-grid">
                    <?php foreach($properties as $pr){ ?>
                        <article class="container-service-main-block">
                            <div class="container-image">
                                <img src="<?= !empty($pr["cover_image"]["url"]) ? base_url("img/uploads/".$pr["cover_image"]["url"]) : base_url("img/image-icon-1280x960.png") ?>" alt="">
                            </div>
                            <div class="container-details">
                                <div class="results-card-topline">
                                    <span class="results-card-badge">Proveedor</span>
                                    <?php if (!empty($pr["updated_at_text"])) { ?>
                                        <span class="results-card-updated"><?= $pr["updated_at_text"] ?></span>
                                    <?php } ?>
                                </div>

                                <div class="container-row-data">
                                    <?php if(!empty($pr["user"])){ ?>
                                        <span class="title-name-main">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24"><path fill="currentColor" d="M15.71 12.71a6 6 0 1 0-7.42 0a10 10 0 0 0-6.22 8.18a1 1 0 0 0 2 .22a8 8 0 0 1 15.9 0a1 1 0 0 0 1 .89h.11a1 1 0 0 0 .88-1.1a10 10 0 0 0-6.25-8.19M12 12a4 4 0 1 1 4-4a4 4 0 0 1-4 4"/></svg>
                                            <?= $pr["user"][0]["first_name"] ?><?= !empty($pr["user"][0]["last_name"]) ? ", ".$pr["user"][0]["last_name"] : "" ?>
                                        </span>
                                        <?php if(!empty($pr["user"][0]["user_name"])){ ?>
                                            <span>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M2 12c0-4.243 0-6.364 1.464-7.682C4.93 3 7.286 3 12 3s7.071 0 8.535 1.318S22 7.758 22 12s0 6.364-1.465 7.682C19.072 21 16.714 21 12 21s-7.071 0-8.536-1.318S2 16.242 2 12"></path><path d="M8.4 8h-.8c-.754 0-1.131 0-1.366.234C6 8.47 6 8.846 6 9.6v.8c0 .754 0 1.131.234 1.366C6.47 12 6.846 12 7.6 12h.8c.754 0 1.131 0 1.366-.234C10 11.53 10 11.154 10 10.4v-.8c0-.754 0-1.131-.234-1.366C9.53 8 9.154 8 8.4 8M6 16h4m4-8h4m-4 4h4m-4 4h4"></path></g></svg>
                                                <?= $pr["user"][0]["user_name"] ?>
                                            </span>
                                        <?php } ?>
                                    <?php } ?>
                                    <?php if(!empty($pr["user_address"])){ ?>
                                        <span>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2c-4.4 0-8 3.6-8 8c0 5.4 7 11.5 7.3 11.8c.2.1.5.2.7.2s.5-.1.7-.2C13 21.5 20 15.4 20 10c0-4.4-3.6-8-8-8m0 17.7c-2.1-2-6-6.3-6-9.7c0-3.3 2.7-6 6-6s6 2.7 6 6s-3.9 7.7-6 9.7M12 6c-2.2 0-4 1.8-4 4s1.8 4 4 4s4-1.8 4-4s-1.8-4-4-4m0 6c-1.1 0-2-.9-2-2s.9-2 2-2s2 .9 2 2s-.9 2-2 2"/></svg>
                                            <?= $pr["user_address"][0]["address"] ?>
                                        </span>
                                    <?php } ?>
                                </div>

                                <?php if (!empty($pr["specialties"])) { ?>
                                    <div class="results-specialties-row">
                                        <?php foreach ($pr["specialties"] as $specialty) { ?>
                                            <span class="results-service-chip"><?= $specialty["name"] ?></span>
                                        <?php } ?>
                                    </div>
                                <?php } ?>

                                <div class="container-btns-redirect">
                                    <?php if(!empty($pr["user"])){ ?>
                                        <a href="https://wa.me/<?= $pr["user"][0]["phone"] ?>?text=Hola,%20me%20interesa%20tu%20servicio" class="whatsapp-btn-contact-link service-stats-whatsapp" data-provider-user-id="<?= (int) ($pr["user"][0]["id"] ?? 0) ?>" data-service-id="0" target="_blank">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 16 16"><path fill="currentColor" d="M11.42 9.49c-.19-.09-1.1-.54-1.27-.61s-.29-.09-.42.1s-.48.6-.59.73s-.21.14-.4 0a5.1 5.1 0 0 1-1.49-.92a5.3 5.3 0 0 1-1-1.29c-.11-.18 0-.28.08-.38s.18-.21.28-.32a1.4 1.4 0 0 0 .18-.31a.38.38 0 0 0 0-.33c0-.09-.42-1-.58-1.37s-.3-.32-.41-.32h-.4a.72.72 0 0 0-.5.23a2.1 2.1 0 0 0-.65 1.55A3.6 3.6 0 0 0 5 8.2A8.3 8.3 0 0 0 8.19 11c.44.19.78.3 1.05.39a2.5 2.5 0 0 0 1.17.07a1.93 1.93 0 0 0 1.26-.88a1.67 1.67 0 0 0 .11-.88c-.05-.07-.17-.12-.36-.21"/><path fill="currentColor" d="M13.29 2.68A7.36 7.36 0 0 0 8 .5a7.44 7.44 0 0 0-6.41 11.15l-1 3.85l3.94-1a7.4 7.4 0 0 0 3.55.9H8a7.44 7.44 0 0 0 5.29-12.72M8 14.12a6.1 6.1 0 0 1-3.15-.87l-.22-.13l-2.34.61l.62-2.28l-.14-.23a6.18 6.18 0 0 1 9.6-7.65a6.12 6.12 0 0 1 1.81 4.37A6.19 6.19 0 0 1 8 14.12"/></svg>
                                            WhatsApp
                                        </a>
                                    <?php } ?>
                                    <a href="<?= site_url("result_provider/".$pr["id"]) ?>" class="redirect-view">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 32 32"><path fill="currentColor" d="M30.94 15.66A16.69 16.69 0 0 0 16 5A16.69 16.69 0 0 0 1.06 15.66a1 1 0 0 0 0 .68A16.69 16.69 0 0 0 16 27a16.69 16.69 0 0 0 14.94-10.66a1 1 0 0 0 0-.68M16 25c-5.3 0-10.9-3.93-12.93-9C5.1 10.93 10.7 7 16 7s10.9 3.93 12.93 9C26.9 21.07 21.3 25 16 25"/><path fill="currentColor" d="M16 10a6 6 0 1 0 6 6a6 6 0 0 0-6-6m0 10a4 4 0 1 1 4-4a4 4 0 0 1-4 4"/></svg>
                                        Ver perfil
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php } ?>
                </div>
                <nav class="pagination" role="navigation" aria-label="pagination">
                    <ul class="pagination-list">
                        <?php for ($i = 0; $i < $quantity_block_nav; $i++){ ?>
                            <li>
                                <a class="pagination-link <?= $number_position == $i+1 ? "is-current" : "" ?>" data-value="<?= $i + 1 ?>" aria-label="Page <?= $i + 1 ?>" aria-current="page"><?= $i + 1 ?></a>
                            </li>
                        <?php } ?>
                    </ul>
                </nav>
            <?php }else{ ?>
                <div class="container-search-map-data">
                    <div class="results-map-layout">
                        <section class="results-map-list-panel">
                            <div class="results-map-list-panel__header">
                                <span class="results-map-list-panel__eyebrow">Explorar ubicaciones</span>
                                <h3>Provincias con proveedores</h3>
                                <p>Selecciona una provincia disponible para centrar el mapa y acotar los resultados.</p>
                            </div>

                            <div class="container-column-view-data-map-macro">
                                <?php foreach($provinces as $provinceItem){ ?>
                                    <div class="container-column-view-data-map">
                                        <button type="button" class="link-h4-filter-addres" data-province="<?= $provinceItem["province"] ?>"><?= $provinceItem["province"] ?></button>
                                        <p class="results-province-count"><?= $provinceItem["total"] ?> proveedores</p>
                                        <?php if (!empty($provinceCities[$provinceItem["province"]])) { ?>
                                            <ul class="results-city-list">
                                                <?php foreach($provinceCities[$provinceItem["province"]] as $cit){ ?>
                                                    <li>
                                                        <button type="button" class="link-li-filter-addres" data-city="<?= $cit["city"] ?>" data-province="<?= $provinceItem["province"] ?>">
                                                            <span><?= $cit["city"] ?></span>
                                                            <strong><?= $cit["total"] ?></strong>
                                                        </button>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </section>

                        <section class="container-map-main-search">
                            <div class="results-map-toolbar">
                                <div>
                                    <span class="results-map-toolbar__eyebrow">Vista mapa</span>
                                    <strong>Distribución geográfica de proveedores</strong>
                                </div>
                                <span class="results-map-toolbar__status"><?= $quantity ?> resultados</span>
                            </div>
                            <div id="map"></div>
                        </section>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

@endsection

@section('js')
<?php if ($mode != 1){ ?>
<script>
    const mapContainer = document.getElementById("map");
    if (mapContainer) {
        let map;
        let usingLeaflet = false;
        let leafletMap;
        let leafletMarkers = [];
        let zoom = 6;
        let center_map = { lat: 40.4168, lng: -3.7038 };
        let data_temp = [];

        const getValidMapLocations = () => {
            return data_temp
                .map((location) => ({
                    ...location,
                    latNum: Number.parseFloat(location.lat),
                    lngNum: Number.parseFloat(location.lng),
                }))
                .filter((location) => Number.isFinite(location.latNum) && Number.isFinite(location.lngNum));
        };

        const syncMapState = (lat, lng, nextZoom = null) => {
            document.getElementById("latitude").value = lat;
            document.getElementById("longitude").value = lng;
            actualizarParametro("latitude", lat);
            actualizarParametro("longitude", lng);

            if (nextZoom !== null && Number.isFinite(nextZoom)) {
                document.getElementById("zoom").value = nextZoom;
                actualizarParametro("zoom", nextZoom);
                zoom = nextZoom;
            }

            center_map.lat = lat;
            center_map.lng = lng;
        };

        const focusGoogleMapOnResults = (locations) => {
            if (!map || !locations.length) {
                return;
            }

            if (locations.length === 1) {
                const onlyLocation = locations[0];
                const singleZoom = Math.max(13, zoom || 13);
                map.setCenter({ lat: onlyLocation.latNum, lng: onlyLocation.lngNum });
                map.setZoom(singleZoom);
                syncMapState(onlyLocation.latNum, onlyLocation.lngNum, singleZoom);
                return;
            }

            const bounds = new google.maps.LatLngBounds();
            locations.forEach((location) => {
                bounds.extend({ lat: location.latNum, lng: location.lngNum });
            });
            map.fitBounds(bounds, 60);

            google.maps.event.addListenerOnce(map, "idle", () => {
                const center = map.getCenter();
                if (!center) {
                    return;
                }

                syncMapState(center.lat(), center.lng(), map.getZoom());
            });
        };

        const focusLeafletMapOnResults = (locations) => {
            if (!leafletMap || !locations.length) {
                return;
            }

            if (locations.length === 1) {
                const onlyLocation = locations[0];
                const singleZoom = Math.max(13, zoom || 13);
                leafletMap.setView([onlyLocation.latNum, onlyLocation.lngNum], singleZoom);
                syncMapState(onlyLocation.latNum, onlyLocation.lngNum, singleZoom);
                return;
            }

            const bounds = L.latLngBounds(locations.map((location) => [location.latNum, location.lngNum]));
            leafletMap.fitBounds(bounds, { padding: [50, 50] });

            const center = leafletMap.getCenter();
            syncMapState(center.lat, center.lng, leafletMap.getZoom());
        };

        const showMapError = (message) => {
            mapContainer.innerHTML = `<div class="map-error">${message}</div>`;
        };

        const useGoogleMaps = () => window.google && window.google.maps && !window.__gmAuthFailed;

        const loadLeaflet = () => {
            if (window.L) {
                return Promise.resolve();
            }

            if (!document.querySelector("link[data-leaflet]")) {
                const leafletCss = document.createElement("link");
                leafletCss.rel = "stylesheet";
                leafletCss.href = "https://unpkg.com/leaflet@1.9.4/dist/leaflet.css";
                leafletCss.dataset.leaflet = "true";
                document.head.appendChild(leafletCss);
            }

            return new Promise((resolve, reject) => {
                const existingScript = document.querySelector("script[data-leaflet]");
                if (existingScript) {
                    existingScript.addEventListener("load", resolve);
                    existingScript.addEventListener("error", () => reject(new Error("Leaflet failed to load")));
                    return;
                }

                const script = document.createElement("script");
                script.src = "https://unpkg.com/leaflet@1.9.4/dist/leaflet.js";
                script.async = true;
                script.dataset.leaflet = "true";
                script.onload = resolve;
                script.onerror = () => reject(new Error("Leaflet failed to load"));
                document.head.appendChild(script);
            });
        };

        function actualizarParametro(parametro, valor) {
            let url = new URL(window.location.href);
            url.searchParams.set(parametro, valor); // Agrega o actualiza el parametro
            window.history.pushState({}, "", url); // Modifica la URL sin recargar
        }

        function initGoogleMap() {
            map = new google.maps.Map(mapContainer, {
                center: center_map, // Madrid
                zoom: zoom,
                streetViewControl: false,
                styles: [
                        {
                            featureType: "all",
                            elementType: "labels.text.fill",
                            stylers: [{ color: "#5a5a5c" }] // Color del texto de las ciudades
                        },
                        {
                            featureType: "administrative", // Para mostrar nombres de ciudades/provincias/estados
                            elementType: "geometry",
                            stylers: [{ visibility: "on" }]
                        },
                        {
                            featureType: "administrative.locality", // Ciudades
                            elementType: "labels",
                            stylers: [{ visibility: "on" }]
                        },
                        {
                            featureType: "administrative.province", // Provincias
                            elementType: "labels",
                            stylers: [{ visibility: "on" }]
                        },
                        {
                            featureType: "administrative.country", // Paises
                            elementType: "labels",
                            stylers: [{ visibility: "on" }]
                        },
                        {
                            featureType: "poi", // Ocultar puntos de interes (negocios, parques, etc.)
                            stylers: [{ visibility: "off" }]
                        },
                        {
                            featureType: "road", // Ocultar calles
                            stylers: [{ visibility: "on" }]
                        },
                        {
                            featureType: "transit", // Ocultar transporte publico
                            stylers: [{ visibility: "off" }]
                        },
                        {
                            featureType: "water", // Mantener los cuerpos de agua visibles
                            elementType: "geometry",
                            stylers: [{ color: "#b3b4f9" }]
                        }
                    ],
                restriction: {
                    latLngBounds: {
                        north: 44.0,
                        south: 35.0,
                        west: -10.0,
                        east: 5.0
                    },
                    strictBounds: true
                }
            });

            // map center get
            map.addListener("center_changed", function () {
                document.getElementById("latitude").value = map.getCenter().lat()
                document.getElementById("longitude").value = map.getCenter().lng()
                
                actualizarParametro("latitude", map.getCenter().lat());
                actualizarParametro("longitude", map.getCenter().lng());
            });

            // Evento cuando el usuario cambia el zoom
            map.addListener("zoom_changed", function () {
                document.getElementById("zoom").value = map.getZoom();
                actualizarParametro("zoom", map.getZoom());
            });

            let activeInfoWindow = null;
            // Agregar marcadores al mapa
            data_temp.forEach((location) => {
                if (location.lat && location.lng){
                    let lat = parseFloat(location.lat);
                    let lng = parseFloat(location.lng);
                    
                    const marker = new google.maps.Marker({
                        position: { lat: lat, lng: lng },
                        map: map,
                        icon: {
                            url: "/img/kconecta_icon.webp",
                            scaledSize: new google.maps.Size(34, 34)
                        },
                        title: `Cantidad: ${location.quantity}`
                    });
        
                    // InfoWindow con detalles adicionales
                    const infoWindow = new google.maps.InfoWindow({
                        content: `
                            <div class="container-map-details-info-app-main">
                                <h3>${location.title}</h3>
                                ${location.logo_url ? `<img src="${location.logo_url}" alt="${location.title}" class="map-service-logo" loading="lazy">` : ""}
                                <div class="service-rating-chip">
                                    <span class="service-rating-chip__stars">${Number(location.ratings_count || 0) > 0 ? `${Number(location.average_stars || 0).toFixed(1)} <span class="service-rating-star-icon">★</span>` : `Sin valoraciones`}</span>
                                    <span class="service-rating-chip__count">${Number(location.ratings_count || 0) > 0 ? `(${Number(location.ratings_count)} reseñas)` : ``}</span>
                                </div>
                                <a href="${location.provider_url || `/result_provider/${location.provider_user_id}`}" class="a-redirect-view">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 32 32"><path fill="#ffffff" d="M30.94 15.66A16.69 16.69 0 0 0 16 5A16.69 16.69 0 0 0 1.06 15.66a1 1 0 0 0 0 .68A16.69 16.69 0 0 0 16 27a16.69 16.69 0 0 0 14.94-10.66a1 1 0 0 0 0-.68M16 25c-5.3 0-10.9-3.93-12.93-9C5.1 10.93 10.7 7 16 7s10.9 3.93 12.93 9C26.9 21.07 21.3 25 16 25"/><path fill="#ffffff" d="M16 10a6 6 0 1 0 6 6a6 6 0 0 0-6-6m0 10a4 4 0 1 1 4-4a4 4 0 0 1-4 4"/></svg>
                                    Ver detalle
                                </a>
                            </div>
                        `
                    });
        
                    // Mostrar InfoWindow al hacer clic en el marcador
                    marker.addListener("click", () => {
                        if (activeInfoWindow) {
                            activeInfoWindow.close();
                        }
        
                        // Abrir la nueva InfoWindow y actualizar la variable
                        infoWindow.open(map, marker);
                        activeInfoWindow = infoWindow;
                    });
                }
            });

            focusGoogleMapOnResults(getValidMapLocations());
        }

        const initLeafletMap = async () => {
            try {
                await loadLeaflet();
            } catch (error) {
                showMapError("No se pudo cargar el mapa.");
                return;
            }

            if (!leafletMap) {
                mapContainer.innerHTML = "";
                leafletMap = L.map(mapContainer, {
                    zoomControl: true,
                });

                L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                    maxZoom: 19,
                    attribution: "(c) OpenStreetMap",
                }).addTo(leafletMap);

                leafletMap.on("moveend", () => {
                    const center = leafletMap.getCenter();
                    document.getElementById("latitude").value = center.lat;
                    document.getElementById("longitude").value = center.lng;

                    actualizarParametro("latitude", center.lat);
                    actualizarParametro("longitude", center.lng);
                });

                leafletMap.on("zoomend", () => {
                    const currentZoom = leafletMap.getZoom();
                    document.getElementById("zoom").value = currentZoom;
                    actualizarParametro("zoom", currentZoom);
                });
            }

            leafletMap.setView([center_map.lat, center_map.lng], zoom);

            leafletMarkers.forEach((marker) => {
                leafletMap.removeLayer(marker);
            });
            leafletMarkers = [];

            const icon = L.icon({
                iconUrl: "/img/kconecta_icon.webp",
                iconSize: [34, 34],
                iconAnchor: [17, 17],
                popupAnchor: [0, -18],
            });

            data_temp.forEach((location) => {
                if (location.lat && location.lng) {
                    let lat = parseFloat(location.lat);
                    let lng = parseFloat(location.lng);

                    const marker = L.marker([lat, lng], { icon: icon });
                    marker.addTo(leafletMap);

                    marker.bindPopup(`
                        <div class="container-map-details-info-app-main">
                            <h3>${location.title}</h3>
                            ${location.logo_url ? `<img src="${location.logo_url}" alt="${location.title}" class="map-service-logo" loading="lazy">` : ""}
                            <div class="service-rating-chip">
                                <span class="service-rating-chip__stars">${Number(location.ratings_count || 0) > 0 ? `${Number(location.average_stars || 0).toFixed(1)} <span class="service-rating-star-icon">★</span>` : `Sin valoraciones`}</span>
                                <span class="service-rating-chip__count">${Number(location.ratings_count || 0) > 0 ? `(${Number(location.ratings_count)} reseñas)` : ``}</span>
                            </div>
                            <a href="${location.provider_url || `/result_provider/${location.provider_user_id}`}" class="a-redirect-view">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 32 32"><path fill="#ffffff" d="M30.94 15.66A16.69 16.69 0 0 0 16 5A16.69 16.69 0 0 0 1.06 15.66a1 1 0 0 0 0 .68A16.69 16.69 0 0 0 16 27a16.69 16.69 0 0 0 14.94-10.66a1 1 0 0 0 0-.68M16 25c-5.3 0-10.9-3.93-12.93-9C5.1 10.93 10.7 7 16 7s10.9 3.93 12.93 9C26.9 21.07 21.3 25 16 25"/><path fill="#ffffff" d="M16 10a6 6 0 1 0 6 6a6 6 0 0 0-6-6m0 10a4 4 0 1 1 4-4a4 4 0 0 1-4 4"/></svg>
                                Ver detalle
                            </a>
                        </div>
                    `);

                    leafletMarkers.push(marker);
                }
            });

            focusLeafletMapOnResults(getValidMapLocations());
        };

        const address_input = document.getElementById("address");
        const city_input = document.getElementById("city");
        const province_input = document.getElementById("province");

        const latitude_input = document.getElementById("latitude").value;
        const longitude_input = document.getElementById("longitude").value;
        const zoom_input = document.getElementById("zoom").value;

        if (latitude_input && latitude_input != null && latitude_input != undefined && longitude_input && longitude_input != null && longitude_input != undefined){center_map.lat = parseFloat(latitude_input); center_map.lng = parseFloat(longitude_input);}
        if (zoom_input && zoom_input != null && zoom_input != undefined){zoom = parseInt(zoom_input)}

        const data_for_map = async (useGoogle) => {
            try {
                if (!useGoogle) {
                    usingLeaflet = true;
                }
                const form_data = new FormData(document.getElementById("form-filter-result"));
                const query_string = new URLSearchParams(form_data).toString();
                const response = await fetch("/api/services_for_map?" + query_string, {
                    method: "GET",
                });
                const data = await response.json();
                const applyData = async () => {
                    data_temp = Array.isArray(data.data) ? data.data : [];
                    if (useGoogle) {
                        initGoogleMap();
                    } else {
                        await initLeafletMap();
                    }
                };

                if (useGoogle && address_input.value && (!city_input.value && !province_input.value)){
                    const geocoder = new google.maps.Geocoder();
                    
                    geocoder.geocode({ address: address_input.value }, function(results, status) {
                        if (status === "OK") {
                            const location = results[0].geometry.location;
                            document.getElementById("latitude").value = location.lat();
                            document.getElementById("longitude").value = location.lng();
                            document.getElementById("zoom").value = 13;
                            zoom = 13;
                            center_map.lat = location.lat(); 
                            center_map.lng = location.lng();
                        }

                        applyData();
                    });
                }else{
                    await applyData();
                }
            } catch (error) {
                showMapError("No se pudo cargar los datos del mapa.");
            }
        }

        window.__gmAuthFailureCallback = () => {
            if (!usingLeaflet) {
                data_for_map(false);
            }
        };

        const waitForGoogleMaps = (attempt = 0) => {
            if (window.__gmAuthFailed) {
                data_for_map(false);
                return;
            }
            if (useGoogleMaps()) {
                data_for_map(true);
                return;
            }
            if (attempt >= 20) {
                data_for_map(false);
                return;
            }
            setTimeout(() => waitForGoogleMaps(attempt + 1), 200);
        };

        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", () => waitForGoogleMaps());
        } else {
            waitForGoogleMaps();
        }
    }
</script>   
<?php } ?>
<script>
    function bindServiceWhatsappTrackingFromList() {
        document.querySelectorAll('.service-stats-whatsapp').forEach((anchor) => {
            anchor.addEventListener('click', async () => {
                const providerUserId = Number(anchor.getAttribute('data-provider-user-id') || 0);
                const serviceId = Number(anchor.getAttribute('data-service-id') || 0);
                if (!providerUserId) return;

                const formData = new FormData();
                formData.append('provider_user_id', String(providerUserId));
                formData.append('service_id', String(serviceId || 0));
                formData.append('channel', 'whatsapp');

                try {
                    await fetch('/api/service_stats/register_contact_click', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin',
                    });
                } catch (error) {
                    // no-op: analytics should not block navigation
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', bindServiceWhatsappTrackingFromList);

    const form_filter = document.getElementById("form-filter-result");
    const btn_clear_service_filters = document.getElementById("btn-clear-service-filters");
    const controll_inputs_filters = ()=>{
        const inputs = form_filter.querySelectorAll("input");
        inputs.forEach((input) =>{
            if (input.value === "" || input.value === "0"){
                input.setAttribute("disabled", true);
            }else{
                input.removeAttribute("disabled");
            }
        });
        form_filter.submit();
    }
    form_filter.addEventListener("submit", (e)=>{
        e.preventDefault();
        controll_inputs_filters();
    })

    if (btn_clear_service_filters){
        btn_clear_service_filters.addEventListener("click", ()=>{
            document.getElementById("input-page-nav").value = "1";
            document.getElementById("city").value = "";
            document.getElementById("province").value = "";
            document.getElementById("latitude").value = "";
            document.getElementById("longitude").value = "";
            document.getElementById("zoom").value = "";
            document.getElementById("address").value = "";

            document.querySelectorAll('input[name="sti[]"]').forEach((checkbox) => {
                checkbox.checked = false;
            });

            controll_inputs_filters();
        });
    }

    function obtenerCoordenadas(ciudad, provincia, pais) {
        if (!window.google || !window.google.maps) {
            controll_inputs_filters();
            return;
        }
        const geocoder = new google.maps.Geocoder();
        let direccion = `${provincia}, ${pais}`;
        if (ciudad){
            direccion = ciudad+", "+ direccion;
        }
        geocoder.geocode({ address: direccion }, function(results, status) {
            if (status === "OK") {
                const location = results[0].geometry.location;
                document.getElementById("latitude").value = location.lat();
                document.getElementById("longitude").value = location.lng();
                if (ciudad){
                    document.getElementById("zoom").value = 11;
                }else{
                    document.getElementById("zoom").value = 9;
                }
                controll_inputs_filters();
            }
        });
    }

    const card_grid = document.querySelector(".container-body-data-result");
    const container_nav_section = document.querySelector(".container-title-section");
    const btn_open = document.querySelector(".btn-open");
    const btn_close = document.querySelector(".btn-close");
    btn_close.addEventListener("click", ()=>{
        container_nav_section.style.display = "none";
        card_grid.style.width = "100%";
        btn_open.style.display = "flex";
    })
    btn_open.addEventListener("click", ()=>{
        container_nav_section.style.display = "flex";
        card_grid.style.width = "calc(100% - 18rem)";
        btn_open.style.display = "none";
    })
    
    const navs = document.querySelectorAll(".pagination-link");
    navs.forEach(nav =>{
        nav.addEventListener("click", ()=>{
            document.getElementById("input-page-nav").value = nav.dataset.value;
            controll_inputs_filters();
        })
    });

    const btns_change_mode = document.querySelectorAll(".button-change-page-result");
    btns_change_mode.forEach(btn =>{
        btn.addEventListener("click", ()=>{
            document.getElementById("mode").value = btn.dataset.mode;
            controll_inputs_filters();
        })
    });
    const link_li_filter_addres = document.querySelectorAll(".link-li-filter-addres");
    link_li_filter_addres.forEach(li =>{
        li.addEventListener("click", ()=>{
            const city = li.dataset.city;
            const province = li.dataset.province;
            document.getElementById("address").value = city + ", " + province;
            document.getElementById("city").value = city;
            document.getElementById("province").value = province;
            obtenerCoordenadas(city, province, "España");
        })
    })

    const link_h4_filter_addres = document.querySelectorAll(".link-h4-filter-addres");
    link_h4_filter_addres.forEach(h4 =>{
        h4.addEventListener("click", ()=>{
            const province = h4.dataset.province;
            document.getElementById("address").value = province;
            document.getElementById("city").value = "";
            document.getElementById("province").value = province;
            obtenerCoordenadas("", province, "España");
        });
    })

</script>
<script src="<?= base_url("js/helpers.js") ?>"></script>
<script src="<?= base_url("js/format_input.js") ?>"></script>
@endsection
