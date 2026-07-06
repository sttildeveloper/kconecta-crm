@extends('layouts.page')

@section('nav_option')
<a href="<?= site_url() ?>">
    <span>Ir al inicio</span>
</a>
@endsection

@section('css')
    <link rel="stylesheet" href="<?= base_url()."css/libraries/swiper-bundle.min.css" ?>">
    <script src="<?= base_url()."js/libraries/swiper-bundle.min.js" ?>"></script>
    <script src="<?= base_url()."js/libraries/bulma.modal.min.js" ?>"></script>
    <link rel="stylesheet" href="<?= base_url()."css/page/details.css" ?>">
    <style>
        .service-rating-card { border: 1px solid #e8ecf3; border-radius: 14px; padding: 16px; margin: 14px 0 6px; background: #fff; }
        .service-rating-stars { font-size: 1.35rem; letter-spacing: 1px; color: #d1d5db; }
        .service-rating-stars .filled { color: #f59e0b; }
        .service-rating-caption { color: #6b7280; font-size: .93rem; }
        .service-rating-inline { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-top: 10px; }
        .service-rating-inline .input { max-width: 240px; }
        .service-rating-inline .select { min-width: 116px; }
        .service-rating-feedback { margin-top: 10px; font-size: .92rem; display: none; }
        .service-rating-feedback.success { color: #15803d; display: block; }
        .service-rating-feedback.error { color: #b91c1c; display: block; }
        .provider-summary-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .7rem; width: 100%; }
        .provider-summary-card { border: 1px solid #e6ecf7; border-radius: 12px; background: #fff; padding: .8rem; }
        .provider-summary-card h4 { margin: 0 0 .2rem; font-size: .76rem; letter-spacing: .04em; text-transform: uppercase; color: #6b7280; font-weight: 800; }
        .provider-summary-card p { margin: 0; font-size: .96rem; color: #22324b; font-weight: 700; }
        .provider-services-card .service-item + .service-item { margin-top: .75rem; padding-top: .75rem; border-top: 1px solid #e7edf7; }
        .provider-services-card .service-item h4 { margin: 0 0 .2rem; font-size: .98rem; font-weight: 800; color: #10213b; }
        .provider-services-card .service-item p { margin: 0; font-size: .88rem; line-height: 1.45; color: #44546c; }
        .provider-services-card .service-item .service-links { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .55rem; }
        .provider-services-card .service-item .service-links a { display: inline-flex; align-items: center; gap: .35rem; padding: .38rem .72rem; border-radius: 999px; border: 1px solid var(--details-border); color: var(--details-accent); font-weight: 700; font-size: .82rem; text-decoration: none; }
        .provider-sidebar-links { display: grid; gap: .5rem; width: 100%; margin-top: .8rem; }
        .provider-sidebar-links a { display: inline-flex; align-items: center; justify-content: center; gap: .35rem; min-height: 40px; border-radius: 10px; border: 1px solid var(--details-border); color: var(--details-accent); font-weight: 700; background: #fff; text-decoration: none; }
        .provider-sidebar-links a.primary { background: #4bb7c6; color: #fff; border-color: transparent; }
        .provider-sidebar-links a.secondary { background: #123f6e; color: #fff; border-color: transparent; }
        @media screen and (max-width: 700px) {
            .provider-summary-grid { grid-template-columns: minmax(0, 1fr); }
        }
    </style>
@endsection

@section('content')
<?php
    $galleryImages = $provider['gallery_images'] ?? [base_url()."img/image-icon-1280x960.png"];
    $heroImage = $galleryImages[0] ?? base_url()."img/image-icon-1280x960.png";
    $providerUserId = (int) ($provider['id'] ?? 0);
    $authUser = auth()->user();
    $canRateServiceProvider = $authUser
        && (int) ($authUser->user_level_id ?? 0) === \App\Models\User::LEVEL_FINAL_CLIENT
        && method_exists($authUser, 'hasVerifiedEmail')
        && $authUser->hasVerifiedEmail();
?>
<div class="container-main-body is-service-detail">
    <div class="container-column-1">
        <div class="container-image">
            <div class="swiper swiper-main">
                <div class="swiper-wrapper">
                    <?php foreach($galleryImages as $imageUrl){ ?>
                    <div class="swiper-slide">
                        <img src="<?= $imageUrl ?>" alt="Imagen del proveedor" />
                    </div>
                    <?php } ?>
                </div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>
            <div class="gallery-toolbar">
                <div class="swiper swiper-thumbs" aria-label="Miniaturas de la galeria del proveedor">
                    <div class="swiper-wrapper">
                        <?php foreach($galleryImages as $imageUrl){ ?>
                        <div class="swiper-slide">
                            <img src="<?= $imageUrl ?>" alt="Miniatura del proveedor" />
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-details-1">
            <h1 class="h1-service"><?= $provider['name'] ?></h1>
            <span class="container-address">
                <?php if (!empty($provider['address_label'])){ ?>
                <span class="service-address-line">
                    <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24"><path fill="none" stroke="#6a7b95" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 13.5a3.5 3.5 0 1 0 0-7a3.5 3.5 0 0 0 0 7"/><path fill="none" stroke="#6a7b95" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 10.5c0 6-7 11-7 11s-7-5-7-11a7 7 0 1 1 14 0"/></svg>
                    <?= $provider['address_label'] ?>
                </span>
                <?php } ?>
                <div class="container-main-map-video-btn">
                    <?php if (!empty($provider['latitude']) && !empty($provider['longitude'])){ ?>
                    <button class="button is-small btn-open-maps-view" id="btn-open-modal-view-map-coord" data-latitude="<?= $provider['latitude'] ?>" data-longitude="<?= $provider['longitude'] ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><g fill="none" stroke="#c026d3" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" color="#c026d3"><path d="M15.129 13.747a.906.906 0 0 1-1.258 0c-1.544-1.497-3.613-3.168-2.604-5.595A3.53 3.53 0 0 1 14.5 6c1.378 0 2.688.84 3.233 2.152c1.008 2.424-1.056 4.104-2.604 5.595M14.5 9.5h.009"/><path d="M2.5 12c0-4.478 0-6.718 1.391-8.109S7.521 2.5 12 2.5c4.478 0 6.718 0 8.109 1.391S21.5 7.521 21.5 12c0 4.478 0 6.718-1.391 8.109S16.479 21.5 12 21.5c-4.478 0-6.718 0-8.109-1.391S2.5 16.479 2.5 12M17 21L3 7m7 7l-6 6"/></g></svg>
                        Ver mapa
                    </button>
                    <?php } ?>
                    <?php if (!empty($provider['primary_video_url'])){ ?>
                    <div class="container-options-view">
                        <button class="button is-small" onclick="openModal(document.getElementById('modal-view-video'))">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 15 15"><path fill="#c026d3" fill-rule="evenodd" d="M4.764 3.122A33 33 0 0 1 7.5 3c.94 0 1.868.049 2.736.122c1.044.088 1.72.148 2.236.27c.47.111.733.258.959.489c.024.025.06.063.082.09c.2.23.33.518.405 1.062c.08.583.082 1.343.082 2.492c0 1.135-.002 1.885-.082 2.46c-.074.536-.204.821-.405 1.054l-.083.09c-.23.234-.49.379-.948.487c-.507.12-1.168.178-2.194.264c-.869.072-1.812.12-2.788.12s-1.92-.048-2.788-.12c-1.026-.086-1.687-.144-2.194-.264c-.459-.108-.719-.253-.948-.487l-.083-.09c-.2-.233-.33-.518-.405-1.054C1.002 9.41 1 8.66 1 7.525c0-1.149.002-1.91.082-2.492c.075-.544.205-.832.405-1.062c.023-.027.058-.065.082-.09c.226-.231.489-.378.959-.489c.517-.122 1.192-.182 2.236-.27M0 7.525c0-2.242 0-3.363.73-4.208c.036-.042.085-.095.124-.135c.78-.799 1.796-.885 3.826-1.056C5.57 2.05 6.527 2 7.5 2s1.93.05 2.82.126c2.03.171 3.046.257 3.826 1.056c.039.04.087.093.124.135c.73.845.73 1.966.73 4.208c0 2.215 0 3.323-.731 4.168a3 3 0 0 1-.125.135c-.781.799-1.778.882-3.773 1.048C9.48 12.951 8.508 13 7.5 13s-1.98-.05-2.87-.124c-1.996-.166-2.993-.25-3.774-1.048a3 3 0 0 1-.125-.135C0 10.848 0 9.74 0 7.525m5.25-2.142a.25.25 0 0 1 .35-.23l4.828 2.118c.2.088.2.37 0 .458L5.6 9.846a.25.25 0 0 1-.35-.229z" clip-rule="evenodd"/></svg>
                            Video
                        </button>
                    </div>
                    <?php } ?>
                    <?php if (!empty($provider['primary_page_url'])){ ?>
                    <a href="<?= $provider['primary_page_url'] ?>" class="button is-small btn-service-web-link" target="_blank" rel="noopener">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16"><path fill="currentColor" d="M6.01 10.49a.47.47 0 0 1-.35-.15c-.2-.2-.2-.51 0-.71l8.49-8.48c.2-.2.51-.2.71 0s.2.51 0 .71l-8.5 8.48c-.1.1-.23.15-.35.15"/><path fill="currentColor" d="M14.5 7c-.28 0-.5-.22-.5-.5V2H9.5c-.28 0-.5-.22-.5-.5s.22-.5.5-.5h5c.28 0 .5.22.5.5v5c0 .28-.22.5-.5.5m-3 8H2.49C1.67 15 1 14.33 1 13.51V4.49C1 3.67 1.67 3 2.49 3H7.5c.28 0 .5.22.5.5s-.22.5-.5.5H2.49a.49.49 0 0 0-.49.49v9.02c0 .27.22.49.49.49h9.01c.27 0 .49-.22.49-.49V8.5c0-.28.22-.5.5-.5s.5.22.5.5v5.01c0 .82-.67 1.49-1.49 1.49"/></svg>
                        Visita nuestra página web
                    </a>
                    <?php } ?>
                </div>
            </span>
        </div>

        <div class="container-description">
            <p><?= nl2br(e($provider['description'])) ?></p>
        </div>

        <div class="provider-summary-grid">
            <article class="provider-summary-card">
                <h4>Servicios publicados</h4>
                <p><?= $provider['service_count'] ?></p>
            </article>
            <article class="provider-summary-card">
                <h4>Última actualización</h4>
                <p><?= $provider['updated_at_text'] ?: 'Sin fecha' ?></p>
            </article>
        </div>

        <div class="service-rating-card" id="service-rating-card" data-provider-id="<?= $providerUserId ?>">
            <h3 style="font-weight: 700; margin-bottom: 8px;">Valoraciones del proveedor</h3>
            <div class="service-rating-stars" id="service-rating-stars-summary" aria-label="Valoracion promedio"></div>
            <div class="service-rating-caption" id="service-rating-caption">Cargando valoraciones...</div>

            <?php if ($canRateServiceProvider){ ?>
                <form id="service-rating-form" class="service-rating-inline">
                    <div class="select is-small">
                        <select id="service-rating-stars-input" name="stars" required>
                            <option value="">Puntuacion</option>
                            <option value="1">1 estrella</option>
                            <option value="2">2 estrellas</option>
                            <option value="3">3 estrellas</option>
                            <option value="4">4 estrellas</option>
                            <option value="5">5 estrellas</option>
                        </select>
                    </div>
                    <input class="input is-small" type="text" id="service-rating-work-code" name="work_code" maxlength="120" placeholder="Codigo de trabajo" required>
                    <button class="button is-small is-link" type="submit" id="service-rating-submit">Enviar valoracion</button>
                </form>
                <p class="service-rating-feedback" id="service-rating-feedback"></p>
            <?php }else{ ?>
                <p class="service-rating-caption" style="margin-top: 8px;">
                    Solo clientes finales con email verificado pueden valorar con codigo de trabajo.
                </p>
            <?php } ?>
        </div>

        <div class="container-more-data">
            <?php if (!empty($provider['service_types'])){ ?>
            <article class="message service-types-card">
                <div class="message-body">
                    <div class="container-row-free-s">
                        <?php foreach($provider['service_types'] as $serviceTypeName){ ?>
                        <div class="box-li-s">
                            &raquo; <span class="text-span"><?= $serviceTypeName ?></span>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </article>
            <?php } ?>

            <?php if (!empty($provider['services'])){ ?>
            <article class="message provider-services-card" style="width:100%;">
                <div class="message-header">
                    <p>Servicios del proveedor</p>
                </div>
                <div class="message-body">
                    <?php foreach($provider['services'] as $service){ ?>
                    <div class="service-item">
                        <h4><?= $service['title'] ?></h4>
                        <?php if (!empty($service['address_label'])){ ?><p><?= $service['address_label'] ?></p><?php } ?>
                        <?php if (!empty($service['availability'])){ ?><p>Disponibilidad: <?= $service['availability'] ?></p><?php } ?>
                        <?php if (!empty($service['service_types'])){ ?><p><?= implode(' · ', $service['service_types']) ?></p><?php } ?>
                        <div class="service-links">
                            <a href="<?= site_url('result_service/' . $service['id']) ?>">Ver ficha del servicio</a>
                            <?php if (!empty($service['page_url'])){ ?>
                            <a href="<?= $service['page_url'] ?>" target="_blank" rel="noopener">Web</a>
                            <?php } ?>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </article>
            <?php } ?>
        </div>
    </div>

    <div class="container-column-2">
        <div class="container-details-contact-owner">
            <div class="title-block">
                <h3>Perfil del proveedor</h3>
            </div>
            <div class="container-form-message">
                <div class="container-contact">
                    <div class="container-profile">
                        <div class="container-image">
                            <img src="<?= $provider['photo_url'] ?>" alt="<?= $provider['name'] ?>" />
                        </div>
                        <span><?= $provider['name'] ?></span>
                    </div>
                    <div class="details-user-post">
                        <?php if (!empty($provider['user_name'])){ ?>
                        <div class="data-row-in">
                            <span class="span-title">Usuario</span>
                            <span class="span-value"><?= $provider['user_name'] ?></span>
                        </div>
                        <?php } ?>
                        <?php if (!empty($provider['email'])){ ?>
                        <div class="data-row-in">
                            <span class="span-title">Correo</span>
                            <span class="span-value"><?= $provider['email'] ?></span>
                        </div>
                        <?php } ?>
                        <?php if (!empty($provider['phone'])){ ?>
                        <div class="data-row-in">
                            <span class="span-title">Teléfono</span>
                            <span class="span-value"><?= $provider['phone'] ?></span>
                        </div>
                        <?php } ?>
                        <div class="data-row-in">
                            <span class="span-title">Última actualización</span>
                            <span class="span-value"><?= $provider['updated_at_text'] ?></span>
                        </div>
                    </div>
                    <div class="provider-sidebar-links">
                        <?php if (!empty($provider['whatsapp_link'])){ ?>
                        <a href="<?= $provider['whatsapp_link'] ?>" class="primary" target="_blank" rel="noopener">
                            Contactar por WhatsApp
                        </a>
                        <?php } ?>
                        <?php if (!empty($provider['services'][0]['id'])){ ?>
                        <a href="<?= site_url('result_service/' . $provider['services'][0]['id']) ?>" class="secondary">
                            Ver servicio destacado
                        </a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($provider['latitude']) && !empty($provider['longitude'])){ ?>
<div class="modal" id="modal-view-map-coord">
    <div class="modal-background"></div>
    <div class="modal-content box">
        <div id="map"></div>
    </div>
    <button class="button modal-close"></button>
</div>
<?php } ?>

<?php if (!empty($provider['primary_video_url'])){ ?>
<div class="modal" id="modal-view-video">
    <div class="modal-background modal-background-video"></div>
    <div class="modal-content box">
        <div class="container-video">
            <video id="video-app" src="<?= $provider['primary_video_url'] ?>" controlsList="nodownload nofullscreen" disablePictureInPicture></video>
        </div>
        <div class="container-actions">
            <button class="btn-close-modal-video"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"><path fill="none" stroke="#0284c7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18M6 6l12 12"/></svg></button>
            <button id="control-play-pause">
                <svg class="svg-pause" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"><path d="M5 5v14a2 2 0 0 0 2.75 1.84L20 13.74a2 2 0 0 0 0-3.5L7.75 3.14A2 2 0 0 0 5 4.89" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <svg class="svg-play" style="display: none;" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"><path fill="none" stroke="#ffffff" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7c0-1.414 0-2.121.44-2.56C4.878 4 5.585 4 7 4s2.121 0 2.56.44C10 4.878 10 5.585 10 7v10c0 1.414 0 2.121-.44 2.56C9.122 20 8.415 20 7 20s-2.121 0-2.56-.44C4 19.122 4 18.415 4 17zm10 0c0-1.414 0-2.121.44-2.56C14.878 4 15.585 4 17 4s2.121 0 2.56.44C20 4.878 20 5.585 20 7v10c0 1.414 0 2.121-.44 2.56c-.439.44-1.146.44-2.56.44s-2.121 0-2.56-.44C14 19.122 14 18.415 14 17z" color="#ffffff"/></svg>
            </button>
        </div>
    </div>
</div>
<?php } ?>
@endsection

@section('js')
<script src="<?= base_url()."js/index_func.js" ?>"></script>
<script>
    const thumbsSwiper = new Swiper('.swiper-thumbs', {
        spaceBetween: 10,
        slidesPerView: 'auto',
        freeMode: true,
        watchSlidesProgress: true,
    });

    const swiper = new Swiper('.swiper-main', {
        loop: true,
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        thumbs: {
            swiper: thumbsSwiper,
        },
    });

    function renderStarsValue(value) {
        const safeValue = Math.max(0, Math.min(5, Number(value) || 0));
        let html = '';
        for (let i = 1; i <= 5; i++) {
            html += i <= Math.round(safeValue) ? '<span class="filled">&#9733;</span>' : '<span>&#9733;</span>';
        }
        return html;
    }

    async function loadProviderRatingSummary() {
        const card = document.getElementById('service-rating-card');
        const starsEl = document.getElementById('service-rating-stars-summary');
        const captionEl = document.getElementById('service-rating-caption');
        if (!card || !starsEl || !captionEl) return;

        const providerUserId = card.dataset.providerId;
        if (!providerUserId) {
            captionEl.textContent = 'No se pudo identificar el proveedor.';
            return;
        }

        try {
            const response = await fetch('/service-ratings/provider/' + providerUserId, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload && payload.message ? payload.message : 'No se pudo cargar el resumen de valoraciones.');
            }

            const data = payload?.data ?? {};
            const average = Number(data.average_stars || 0);
            const count = Number(data.ratings_count || 0);
            starsEl.innerHTML = renderStarsValue(average);
            captionEl.textContent = average.toFixed(1) + ' / 5 (' + count + ' votos)';

            const myStarsInput = document.getElementById('service-rating-stars-input');
            if (myStarsInput && data.my_stars) {
                myStarsInput.value = String(data.my_stars);
            }
        } catch (error) {
            starsEl.innerHTML = renderStarsValue(0);
            captionEl.textContent = error.message || 'Error al cargar valoraciones.';
        }
    }

    async function submitProviderRating(event) {
        event.preventDefault();

        const submitBtn = document.getElementById('service-rating-submit');
        const feedbackEl = document.getElementById('service-rating-feedback');
        const starsInput = document.getElementById('service-rating-stars-input');
        const workCodeInput = document.getElementById('service-rating-work-code');
        const card = document.getElementById('service-rating-card');
        if (!submitBtn || !feedbackEl || !starsInput || !workCodeInput || !card) return;

        const providerUserId = Number(card.dataset.providerId || 0);
        const stars = Number(starsInput.value || 0);
        const workCode = (workCodeInput.value || '').trim();

        feedbackEl.className = 'service-rating-feedback';
        feedbackEl.textContent = '';
        submitBtn.setAttribute('disabled', 'disabled');
        submitBtn.textContent = 'Enviando...';

        try {
            const response = await fetch('/service-ratings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '<?= csrf_token() ?>'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    provider_user_id: providerUserId,
                    work_code: workCode,
                    stars: stars
                })
            });
            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload && payload.message ? payload.message : 'No se pudo enviar la valoracion.');
            }

            feedbackEl.className = 'service-rating-feedback success';
            feedbackEl.textContent = 'Valoracion enviada correctamente.';
            await loadProviderRatingSummary();
        } catch (error) {
            feedbackEl.className = 'service-rating-feedback error';
            feedbackEl.textContent = error.message || 'Error al enviar la valoracion.';
        } finally {
            submitBtn.removeAttribute('disabled');
            submitBtn.textContent = 'Enviar valoracion';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        loadProviderRatingSummary();
        const ratingForm = document.getElementById('service-rating-form');
        if (ratingForm) {
            ratingForm.addEventListener('submit', submitProviderRating);
        }
    });
</script>
<?php if (!empty($provider['primary_video_url'])){ ?>
<script>
    const video_app = document.getElementById("video-app");
    const btn_control_play_pause = document.getElementById("control-play-pause");
    const btn_close_modal = document.querySelector(".btn-close-modal-video");
    if (video_app && btn_control_play_pause && btn_close_modal){
        btn_control_play_pause.addEventListener("click", ()=>{
            if (video_app.dataset.state === "play"){
                video_app.pause();
                video_app.dataset.state = "pause";
                btn_control_play_pause.querySelector(".svg-play").style.display = "none";
                btn_control_play_pause.querySelector(".svg-pause").removeAttribute("style");
            }else{
                video_app.play();
                video_app.dataset.state = "play";
                btn_control_play_pause.querySelector(".svg-play").removeAttribute("style");
                btn_control_play_pause.querySelector(".svg-pause").style.display = "none";
            }
        });
        document.querySelector(".modal-background-video").addEventListener("click", ()=>{
            video_app.pause();
            video_app.dataset.state = "pause";
            btn_control_play_pause.querySelector(".svg-play").style.display = "none";
            btn_control_play_pause.querySelector(".svg-pause").removeAttribute("style");
            closeModal(document.getElementById('modal-view-video'));
        });
        btn_close_modal.addEventListener("click", ()=>{
            video_app.pause();
            video_app.dataset.state = "pause";
            btn_control_play_pause.querySelector(".svg-play").style.display = "none";
            btn_control_play_pause.querySelector(".svg-pause").removeAttribute("style");
            closeModal(document.getElementById('modal-view-video'));
        });
    }
</script>
<?php } ?>
<?php if (!empty($provider['latitude']) && !empty($provider['longitude'])){ ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= config('services.google.maps_key') ?>&libraries=places" referrerpolicy="strict-origin-when-cross-origin"></script>
<script>
    const btn_open_modal_map = document.getElementById("btn-open-modal-view-map-coord");
    if (btn_open_modal_map){
        btn_open_modal_map.addEventListener("click", ()=>{
            openModal(document.getElementById("modal-view-map-coord"));
        });
    }

    function initMap(initial_position) {
        map = new google.maps.Map(document.getElementById("map"), {
            center: initial_position,
            zoom: 14,
            streetViewControl: false,
        });

        marker = new google.maps.Marker({
            position: initial_position,
            map: map,
            icon: {
                url: "/img/icon-location-main-app.webp",
                scaledSize: new google.maps.Size(30, 42)
            },
        });
    }

    const lat = '<?= $provider['latitude'] ?>';
    const lng = '<?= $provider['longitude'] ?>';
    if (lat && lng){
        window.addEventListener('load', function () {
            initMap({ lat: parseFloat(lat), lng: parseFloat(lng) });
        });
    }
</script>
<?php } ?>
@endsection
