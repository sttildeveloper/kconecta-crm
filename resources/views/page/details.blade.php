@extends('layouts.page')

@section('nav_option')
<a href="<?= site_url() ?>">
    <span>Ir al inicio</span>
</a>
@endsection

@section('css')
    <?php $coverImageUrl = !empty($property["cover_image"]["url"]) ? base_url()."img/uploads/".$property["cover_image"]["url"] : base_url()."img/image-icon-1280x960.png"; ?>
    <link rel="stylesheet" href="<?= base_url()."css/libraries/swiper-bundle.min.css" ?>">
    <script src="<?= base_url()."js/libraries/swiper-bundle.min.js" ?>"></script>
    <script src="<?= base_url()."js/libraries/bulma.modal.min.js" ?>"></script>
    <link rel="stylesheet" href="<?= base_url()."css/page/details.css" ?>">

    <!-- seo -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= $property["title"] ?>">
    <meta property="og:description" content="<?= $property["description"] ?>">
    <meta property="og:image" content="<?= $coverImageUrl ?>">
    <meta property="og:url" content="<?= base_url()."result/".$property["reference"] ?>">
    <meta property="og:site_name" content="<?= base_url() ?>">
@endsection

@section('content')
<div class="container-main-body">
    <input type="hidden" id="app-ref-main" value="<?= $property["id"] ?>">
    <div class="container-column-1">
        <div class="container-image">
            <div class="swiper swiper-main">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <img src="<?= $coverImageUrl ?>" alt="Placeholder image" />
                    </div>
                    <?php foreach($property["more_images"] as $im){ ?>
                    <div class="swiper-slide">
                        <img src="<?= base_url()."img/uploads/".$im["url"] ?>" class="carousel-img-app" alt="Placeholder image" />
                    </div>
                    <?php } ?>
                </div>

                <!-- Botones de navegación -->
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>

                <!-- Paginación -->
                <div class="swiper-pagination"></div>
            </div>
            <div class="gallery-toolbar">
                <div class="swiper swiper-thumbs" aria-label="Miniaturas de la galeria">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <img src="<?= $coverImageUrl ?>" alt="Miniatura principal" />
                        </div>
                        <?php foreach($property["more_images"] as $im){ ?>
                        <div class="swiper-slide">
                            <img src="<?= base_url()."img/uploads/".$im["url"] ?>" alt="Miniatura de imagen" />
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="container-main-share-options container-main-share-options--gallery">
                    <div class="container-block-share">
                        <button id="btn-share">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 20 20"><path fill="#ffffff" d="M12 6V2l7 7l-7 7v-4c-5 0-8.5 1.5-11 5l.8-3l.2-.4A12 12 0 0 1 12 6"/></svg>
                            Compartir
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-details-1">
            <h1>
                <?= $property["title"] ?>
            </h1>
            <!-- <span><?= !empty($property["name_urbanization"])? $property["name_urbanization"]. ", ": "" ?> <?= $property["number"] ?>,<?= $property["address"] ?>, <?= $property["locality"] ?> <a href="#">Ver mapa</a></span> -->
            <span class="container-address">
                <span><?= $property["address"] ?> </span>
                <div class="container-main-map-video-btn">
                    <button class="button is-small btn-open-maps-view" id="btn-open-modal-view-map-coord" data-latitude="<?= !empty($property["property_address"]) ? $property["property_address"][0]["latitude"] : "" ?>" data-longitude="<?= !empty($property["property_address"]) ? $property["property_address"][0]["longitude"] : "" ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><g fill="none" stroke="#c026d3" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" color="#c026d3"><path d="M15.129 13.747a.906.906 0 0 1-1.258 0c-1.544-1.497-3.613-3.168-2.604-5.595A3.53 3.53 0 0 1 14.5 6c1.378 0 2.688.84 3.233 2.152c1.008 2.424-1.056 4.104-2.604 5.595M14.5 9.5h.009"/><path d="M2.5 12c0-4.478 0-6.718 1.391-8.109S7.521 2.5 12 2.5c4.478 0 6.718 0 8.109 1.391S21.5 7.521 21.5 12c0 4.478 0 6.718-1.391 8.109S16.479 21.5 12 21.5c-4.478 0-6.718 0-8.109-1.391S2.5 16.479 2.5 12M17 21L3 7m7 7l-6 6"/></g></svg>
                        Ver mapa
                    </button>
                    <?php if (!empty($property["videos"])){ ?>
                        <div class="container-options-view">
                            <button class="button is-small" onclick="openModal(document.getElementById('modal-view-video'))">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 15 15"><path fill="#c026d3" fill-rule="evenodd" d="M4.764 3.122A33 33 0 0 1 7.5 3c.94 0 1.868.049 2.736.122c1.044.088 1.72.148 2.236.27c.47.111.733.258.959.489c.024.025.06.063.082.09c.2.23.33.518.405 1.062c.08.583.082 1.343.082 2.492c0 1.135-.002 1.885-.082 2.46c-.074.536-.204.821-.405 1.054l-.083.09c-.23.234-.49.379-.948.487c-.507.12-1.168.178-2.194.264c-.869.072-1.812.12-2.788.12s-1.92-.048-2.788-.12c-1.026-.086-1.687-.144-2.194-.264c-.459-.108-.719-.253-.948-.487l-.083-.09c-.2-.233-.33-.518-.405-1.054C1.002 9.41 1 8.66 1 7.525c0-1.149.002-1.91.082-2.492c.075-.544.205-.832.405-1.062c.023-.027.058-.065.082-.09c.226-.231.489-.378.959-.489c.517-.122 1.192-.182 2.236-.27M0 7.525c0-2.242 0-3.363.73-4.208c.036-.042.085-.095.124-.135c.78-.799 1.796-.885 3.826-1.056C5.57 2.05 6.527 2 7.5 2s1.93.05 2.82.126c2.03.171 3.046.257 3.826 1.056c.039.04.087.093.124.135c.73.845.73 1.966.73 4.208c0 2.215 0 3.323-.731 4.168a3 3 0 0 1-.125.135c-.781.799-1.778.882-3.773 1.048C9.48 12.951 8.508 13 7.5 13s-1.98-.05-2.87-.124c-1.996-.166-2.993-.25-3.774-1.048a3 3 0 0 1-.125-.135C0 10.848 0 9.74 0 7.525m5.25-2.142a.25.25 0 0 1 .35-.23l4.828 2.118c.2.088.2.37 0 .458L5.6 9.846a.25.25 0 0 1-.35-.229z" clip-rule="evenodd"/></svg>
                                Video
                            </button>
                        </div>
                    <?php } ?>
                </div>
            </span>
            <div class="container-other-block-door">
                <?php
                    if (!empty($property["esc_block"])){
                        echo "<span class='tag is-white'>Bloque / Esc. &raquo; ". $property["esc_block"] ."</span>";
                    }
                    if (!empty($property["door"])){
                        echo "<span class='tag is-white'>Puerta &raquo; ". $property["door"] ."</span>";
                    }
                ?>
            </div>
        </div>
        <div class="container-details-2">
            <div class="btn-dec">
                <span class="">Tipo</span>
                <span><?= !empty($property["type_name"]) ? $property["type_name"] : "N/A" ?></span>
            </div>
            
            <div class="btn-dec">
                <span class="">Planta</span>
                <span><?= !empty($property["plant"]) ? $property["plant"][0]["name"] : "N/A" ?></span>
            </div>
            <div class="btn-dec">
                <span class="">Tipo de piso</span>
                <span><?php 
                if(!empty($property["types_floors"])){
                    $ctn = 1;
                    foreach($property["types_floors"] as $tf){
                        if (count($property["types_floors"]) == $ctn){
                            if (count($property["types_floors"]) === 1){
                                echo $tf["name"];
                            }else{
                                echo " y ".$tf["name"];
                            }
                        }else{
                            if ($ctn < count($property["types_floors"]) -1 ){
                                echo $tf["name"] .", " ;
                            }else{
                                echo $tf["name"];
                            }
                        }
                        $ctn += 1;
                    }
                }else{
                    echo "N/A";
                }
                ?></span>
            </div>

            <div class="btn-dec">
                <span class="">Categoría</span>
                <span><?= !empty($property["category_name"]) ? $property["category_name"] : "N/A" ?></span>
            </div>
            <div class="btn-dec">
                <span class="">Precio</span>
                <span><?php
                    $priceValue = "";
                    if (intval($property["category_id"]) == 1){
                        $priceValue = $property["rental_price"] ?? "";
                    }else if (intval($property["category_id"]) == 2){
                        $priceValue = $property["sale_price"] ?? "";
                    }
                    echo !empty($priceValue) ? $priceValue . " &euro;" : "N/A";
                    ?>
                </span>
            </div>
            <?php if ((int) ($property["type_id"] ?? 0) === 9 && !empty($property["type_of_terrain"])) { ?>
            <div class="btn-dec">
                <span class="">Tipo de terreno</span>
                <span><?= $property["type_of_terrain"][0]["name"] ?></span>
            </div>
            <?php } ?>
            <?php if ((int) ($property["type_id"] ?? 0) === 9 && !empty($property["terrain_use"])) { ?>
            <div class="btn-dec">
                <span class="">Uso</span>
                <span><?= $property["terrain_use"][0]["name"] ?></span>
            </div>
            <?php } ?>
            <?php if ((int) ($property["type_id"] ?? 0) === 9 && !empty($property["terrain_qualifications"])) { ?>
            <div class="btn-dec">
                <span class="">Tipo de calificación</span>
                <span><?= implode(', ', array_column($property["terrain_qualifications"], "name")) ?></span>
            </div>
            <?php } ?>
            <div class="btn-dec">
                <span class="">M<sup>2</sup> Construidos</span>
                <span><?= !empty($property["meters_built"]) ? $property["meters_built"] . " m<sup>2</sup>" : "N/A" ?></span>
            </div>
            <?php if ((int) ($property["type_id"] ?? 0) !== 9) { ?>
            <div class="btn-dec">
                <span class="">Fianza</span>
                <span><?= !empty($property["rental_price"]) ? $property["rental_price"] . " &euro;" : "N/A" ?></span>
            </div>
            <div class="btn-dec">
                <span class="">Estado de conservación</span>
                <span><?= !empty($property["state_conservation"]) ? $property["state_conservation"][0]["name"] : "N/A" ?></span>
            </div>
            <?php } ?>
        </div>
        <div class="container-description">
            <p><?php 
                // echo $property["description"];
                $text_with_breaks = str_replace('. ', ".\n", $property["description"]);
                $text_with_html_breaks = nl2br($text_with_breaks);
                echo $text_with_html_breaks;
            ?></p>
            <?php if (!empty($property["page_url"])){ ?>
            <a href="<?= $property["page_url"] ?>" class="tag is-link" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16"><path fill="#ffffff" d="M6.01 10.49a.47.47 0 0 1-.35-.15c-.2-.2-.2-.51 0-.71l8.49-8.48c.2-.2.51-.2.71 0s.2.51 0 .71l-8.5 8.48c-.1.1-.23.15-.35.15"/><path fill="#ffffff" d="M14.5 7c-.28 0-.5-.22-.5-.5V2H9.5c-.28 0-.5-.22-.5-.5s.22-.5.5-.5h5c.28 0 .5.22.5.5v5c0 .28-.22.5-.5.5m-3 8H2.49C1.67 15 1 14.33 1 13.51V4.49C1 3.67 1.67 3 2.49 3H7.5c.28 0 .5.22.5.5s-.22.5-.5.5H2.49a.49.49 0 0 0-.49.49v9.02c0 .27.22.49.49.49h9.01c.27 0 .49-.22.49-.49V8.5c0-.28.22-.5.5-.5s.5.22.5.5v5.01c0 .82-.67 1.49-1.49 1.49"/></svg>
                Visita nuestra página web
            </a>
            <?php } ?>
        </div>
        @include('page.partials.details.more_data')
        <div class="container-column-2">
        <div class="container-details-contact-owner">
            <div class="title-block">
                <h3>Pregunta al anunciante</h3>
            </div>
            <div class="container-form-message">
                <!-- <label for="">
                    <span>Mensaje: </span>
                    <textarea name="" id="" rows="3" class="textarea"></textarea>
                </label>
                <label for="">
                    <span>Email:</span>
                    <input type="text" class="input">
                </label>
                <label for="">
                    <span>Teléfono:</span>
                    <input type="text" class="input">
                </label>
                <label for="">
                    <span>Nombre: </span>
                    <input type="text" class="input">
                </label>
                <label for="">
                    <input type="checkbox" name="" id="" class="checkbox"> Aceptar política de privacidad
                </label>
                <button class="button is-link">Contactar por chat</button> -->
                
                <div class="container-contact">
                    <div class="container-profile">
                        <div class="container-image">
                            <img src="<?= base_url("img/photo_profile/").$property["user"]["photo"] ?>" alt="Placeholder image" />
                        </div>
                        <span><?= $property["user"]["user_name"] ?></span>
                    </div>
                    <div class="details-user-post">
                        <div class="data-row-in">
                            <span class="span-title">Publicado por: </span>
                            <span class="span-value"><?= $property["user"]["first_name"] ?><?= !empty($property["user"]["last_name"])? ", ".$property["user"]["last_name"] : ""?></span>
                        </div>
                        <div class="data-row-in">
                            <span class="span-title">Última actualización</span>
                            <span class="span-value">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 36 36"><path fill="#666666" d="M32.25 6H29v2h3v22H4V8h3V6H3.75A1.78 1.78 0 0 0 2 7.81v22.38A1.78 1.78 0 0 0 3.75 32h28.5A1.78 1.78 0 0 0 34 30.19V7.81A1.78 1.78 0 0 0 32.25 6" class="clr-i-outline clr-i-outline-path-1"></path><path fill="#666666" d="M8 14h2v2H8z" class="clr-i-outline clr-i-outline-path-2"></path><path fill="#666666" d="M14 14h2v2h-2z" class="clr-i-outline clr-i-outline-path-3"></path><path fill="#666666" d="M20 14h2v2h-2z" class="clr-i-outline clr-i-outline-path-4"></path><path fill="#666666" d="M26 14h2v2h-2z" class="clr-i-outline clr-i-outline-path-5"></path><path fill="#666666" d="M8 19h2v2H8z" class="clr-i-outline clr-i-outline-path-6"></path><path fill="#666666" d="M14 19h2v2h-2z" class="clr-i-outline clr-i-outline-path-7"></path><path fill="#666666" d="M20 19h2v2h-2z" class="clr-i-outline clr-i-outline-path-8"></path><path fill="#666666" d="M26 19h2v2h-2z" class="clr-i-outline clr-i-outline-path-9"></path><path fill="#666666" d="M8 24h2v2H8z" class="clr-i-outline clr-i-outline-path-10"></path><path fill="#666666" d="M14 24h2v2h-2z" class="clr-i-outline clr-i-outline-path-11"></path><path fill="#666666" d="M20 24h2v2h-2z" class="clr-i-outline clr-i-outline-path-12"></path><path fill="#666666" d="M26 24h2v2h-2z" class="clr-i-outline clr-i-outline-path-13"></path><path fill="#666666" d="M10 10a1 1 0 0 0 1-1V3a1 1 0 0 0-2 0v6a1 1 0 0 0 1 1" class="clr-i-outline clr-i-outline-path-14"></path><path fill="#666666" d="M26 10a1 1 0 0 0 1-1V3a1 1 0 0 0-2 0v6a1 1 0 0 0 1 1" class="clr-i-outline clr-i-outline-path-15"></path><path fill="#666666" d="M13 6h10v2H13z" class="clr-i-outline clr-i-outline-path-16"></path><path fill="none" d="M0 0h36v36H0z"></path></svg>
                                <?= $property["updated_at_text"] ?>
                            </span>
                        </div>
                    </div>
                    <div class="container-contact-header-main">
                        <div>
                            <?php if(!empty($property["user"]["landline_phone"])){ ?>
                                <a href="tel:<?= $property["user"]["landline_phone"] ?>" class="btn-contact-redirect A7x9Vb2QmL-psu" data-i="<?= $property["id"] ?>" data-col="owner_calls">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 24 24"><path fill="#ffffff" d="M3.833 4h4.49L9.77 7.618l-2.325 1.55A1 1 0 0 0 7 10c.003.094 0 .001 0 .001v.021a2 2 0 0 0 .006.134q.008.124.035.33c.039.27.114.642.26 1.08c.294.88.87 2.019 1.992 3.141s2.261 1.698 3.14 1.992c.439.146.81.22 1.082.26a4 4 0 0 0 .463.04l.013.001h.008s.112-.006.001 0a1 1 0 0 0 .894-.553l.67-1.34l4.436.74v4.32c-2.111.305-7.813.606-12.293-3.874S3.527 6.11 3.833 4m5.24 6.486l1.807-1.204a2 2 0 0 0 .747-2.407L10.18 3.257A2 2 0 0 0 8.323 2H3.781c-.909 0-1.764.631-1.913 1.617c-.34 2.242-.801 8.864 4.425 14.09s11.848 4.764 14.09 4.425c.986-.15 1.617-1.004 1.617-1.913v-4.372a2 2 0 0 0-1.671-1.973l-4.436-.739a2 2 0 0 0-2.118 1.078l-.346.693a5 5 0 0 1-.363-.105c-.62-.206-1.481-.63-2.359-1.508s-1.302-1.739-1.508-2.36a5 5 0 0 1-.125-.447z"/></svg>
                                    Llamar
                                </a>
                            <?php } ?>
                        </div>
    
                        <button id="btn-openLoginModal">
                            <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 24 24"><path fill="#666666" d="M22 6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2zm-2 0l-8 5l-8-5zm0 12H4V8l8 5l8-5z"/></svg>
                            Enviar mensaje por correo
                        </button>
                    </div>
                    
                </div>
            </div>
        </div>
        <div class="banners_google_ads">
            <!-- banner_in_details_1 -->
            <ins class="adsbygoogle"
                style="display:block"
                data-ad-client="ca-pub-9259257545893744"
                data-ad-slot="1002920395"
                data-ad-format="auto"
                data-full-width-responsive="true"></ins>

            <!-- banner_in_details_2 -->
            <ins class="adsbygoogle"
                style="display:block"
                data-ad-client="ca-pub-9259257545893744"
                data-ad-slot="5043944044"
                data-ad-format="auto"
                data-full-width-responsive="true"></ins>
        </div>
    </div>
</div>
<!-- Modal -->
@include('page.partials.details.share_login_modal')

<div class="modal" id="modal-view-map-coord">
    <div class="modal-background"></div>
    <div class="modal-content box">
        <div id="map"></div>
    </div>
    <button class="button modal-close"></button>
</div>
<div class="modal" id="modal-view-video">
    <div class="modal-background modal-background-video"></div>
    <div class="modal-content box">
        <div class="container-video">
            <?php if (!empty($property["videos"])){ ?>
                <video id="video-app" src="<?= base_url("video/uploads/".$property["videos"][0]["url"]) ?>" controlsList="nodownload nofullscreen" disablePictureInPicture></video>
            <?php } ?>
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
@endsection

@section('js')   
<script src="<?= base_url()."js/index_func.js" ?>"></script>
<script src="<?= base_url()."js/visits_control.js" ?>"></script>
<script>
    window.KConectaConsent.onAllowed('advertising', function () {
        if (!document.querySelector('script[data-kconecta-adsense]')) {
            const script = document.createElement('script');
            script.async = true;
            script.crossOrigin = 'anonymous';
            script.dataset.kconectaAdsense = 'true';
            script.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9259257545893744';
            script.onload = function () {
                document.querySelectorAll('.adsbygoogle').forEach(function () {
                    (window.adsbygoogle = window.adsbygoogle || []).push({});
                });
            };
            document.head.appendChild(script);
        }
    });
</script>

<script>
    const thumbsSwiper = new Swiper('.swiper-thumbs', {
        spaceBetween: 10,
        slidesPerView: 'auto',
        freeMode: true,
        watchSlidesProgress: true,
    });

    const swiper = new Swiper('.swiper-main', {
        loop: true,
        autoplay: {
            delay: 3000,
        },
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
</script>
<script>
    const video_app = document.getElementById("video-app");
    const btn_control_play_pause = document.getElementById("control-play-pause");
    const btn_close_modal = document.querySelector(".btn-close-modal-video");
    const btn_open_modal_map = document.getElementById("btn-open-modal-view-map-coord");
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
    })
    document.querySelector(".modal-background-video").addEventListener("click", ()=>{
        video_app.pause();
        video_app.dataset.state = "pause";
        btn_control_play_pause.querySelector(".svg-play").style.display = "none";
        btn_control_play_pause.querySelector(".svg-pause").removeAttribute("style");
        closeModal(document.getElementById('modal-view-video'))
    })
    btn_close_modal.addEventListener("click", ()=>{
        video_app.pause();
        video_app.dataset.state = "pause";
        btn_control_play_pause.querySelector(".svg-play").style.display = "none";
        btn_control_play_pause.querySelector(".svg-pause").removeAttribute("style");
        closeModal(document.getElementById('modal-view-video'))
    })
    btn_open_modal_map.addEventListener("click", ()=>{
        openModal(document.getElementById("modal-view-map-coord"));
    })
    video_app?.addEventListener("contextmenu", function(e) {
        e.preventDefault(); // Bloquea el clic derecho en el video
    });

    document.addEventListener("keydown", function(e) {
        if (e.ctrlKey && (e.key === "s" || e.key === "S" || e.key === "u" || e.key === "U")) {
            e.preventDefault(); // Bloquea Ctrl + S (Guardar) y Ctrl + U (Ver código fuente)
        }
    });
    
    document.addEventListener("DOMContentLoaded", function () {
    const images = document.querySelectorAll(".carousel-img-app");

    const loadImage = (img) => {
        img.src = img.getAttribute("src");
    };

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                loadImage(entry.target);
                observer.unobserve(entry.target);
            }
        });
    });

    images.forEach(img => observer.observe(img));
});
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= config('services.google.maps_key') ?>&libraries=places" referrerpolicy="strict-origin-when-cross-origin"></script>
<script>
    function initMap(initial_position) {
        // Coordenadas iniciales (puedes cambiarlo)
        let initialPosition = initial_position; 

        // Crear el mapa
        map = new google.maps.Map(document.getElementById("map"), {
            center: initialPosition,
            zoom: 14,
            streetViewControl: false,
            styles: [
                {
                    featureType: "transit", // Oculta las estaciones de transporte público
                    stylers: [{ visibility: "off" }]
                }
            ]
        });

        // Crear el marcador movible
        marker = new google.maps.Marker({
            position: initialPosition,
            map: map,
            icon: {
                url: "/img/kconecta-map-marker.png",
                scaledSize: new google.maps.Size(38, 38)
            },
        });
    }
    const lat = document.getElementById("btn-open-modal-view-map-coord").dataset.latitude;
    const lng = document.getElementById("btn-open-modal-view-map-coord").dataset.longitude;
    if (lat && lng){
        window.onload = initMap({ lat: parseFloat(lat), lng: parseFloat(lng) });
    }
</script>
@endsection





