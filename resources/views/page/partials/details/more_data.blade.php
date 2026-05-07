<div class="container-more-data">
            <?php
                $hasFacadeSummary = !empty($property["facade"]);
                if (intval($property["type_id"]) != 9 && $hasFacadeSummary){
            ?>
                <article class="message details-section-main details-top-card">
                <div class="message-body">
                    <div class="container-row-free">
                        <?php if (!empty($property["facade"])){ ?>
                        <div class="box-li">
                            <h3 class="text-title-h">Fachada del inmueble</h3>
                            <span class="text-span"><?= $property["facade"][0]["name"] ?> </span>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </article>
            <?php } ?>
            <?php if (intval($property["category_id"]) == 1 && intval($property["type_id"]) != 9){ ?>
                <?php
                    $showHasTenantsTopCard = intval($property["type_id"]) !== 1
                        && intval($property["type_id"]) !== 13
                        && !empty($property["has_tenants"]);
                ?>
                <?php if($showHasTenantsTopCard){ ?>
                <article class="message details-section-main details-top-card">
                    <div class="message-body">
                        <div class="container-row-free">
                            <div class="box-li">
                                <h3 class="text-title-h">Inquilinos</h3>
                                <?php if (strval($property["has_tenants"]) === "1"){ ?>
                                    <span class="text-span">Si tiene</span>
                                <?php }else if (strval($property["has_tenants"]) === "2"){ ?>
                                    <span class="text-span">No tiene</span>
                                <?php }else{ ?>
                                    <span class="text-span">Preguntar</span>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </article>
                <?php } ?>
                <?php
                    $hasRentalSummary = !empty($property["rental_type"]);
                    if($hasRentalSummary){
                ?>
                <article class="message details-section-main details-top-card">
                    <div class="message-body">
                        <div class="container-row-free">
                            <?php if(!empty($property["rental_type"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Tipo de arquiler</h3>
                                <span class="text-span"><?= $property["rental_type"][0]["name"] ?></span>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </article>
                <?php } ?>
                <?php
                    $hasAppropriateForChildren = array_key_exists("appropriate_for_children", $property) && $property["appropriate_for_children"] !== "" && $property["appropriate_for_children"] !== null;
                    $hasPetFriendly = array_key_exists("pet_friendly", $property) && $property["pet_friendly"] !== "" && $property["pet_friendly"] !== null;
                    $hasTenantConditions = !empty($property["max_num_tenants"]) || $hasAppropriateForChildren || $hasPetFriendly;
                    if ((intval($property["type_id"]) === 1 || intval($property["type_id"]) === 13) && $hasTenantConditions){
                ?>
                <article class="message details-section-main details-top-card">
                    <div class="message-body">
                        <div class="container-row-free">
                            <?php if(!empty($property["max_num_tenants"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Número máximo de inquilinos</h3>
                                <span class="text-span"><?= $property["max_num_tenants"] ?></span>
                            </div>
                            <?php } if($hasAppropriateForChildren){ ?>
                            <div class="box-li">
                                <?php if($property["appropriate_for_children"]){
                                    echo "<h3 class='text-title-h'>Apropiado para niños</h3>";
                                 }else{
                                    echo "<h3 class='text-title-h'>No apropiado para niños</h3>";
                                 } ?>
                            </div>
                            <?php } ?>
                            <?php if($hasPetFriendly){ ?>
                            <div class="box-li">
                                <?php if($property["pet_friendly"]){
                                    echo "<h3 class='text-title-h'>Se admiten mascotas</h3>";
                                 }else{
                                    echo "<h3 class='text-title-h'>No se admiten mascotas</h3>";
                                 } ?>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </article>
                <?php } ?>
            <?php
                }
            ?>
            <?php
                $hasSaleDetails = !empty($property["type_of_terrain"])
                    || !empty($property["wheeled_access"])
                    || !empty($property["nearest_municipality_distance"])
                    || !empty($property["land_size"])
                    || !empty($property["typology"])
                    || !empty($property["community_expenses"])
                    || !empty($property["ibi"])
                    || !empty($property["mortgage_rate"])
                    || !empty($property["reason_for_sale"]);
                if (intval($property["category_id"]) == 2 && $hasSaleDetails){
            ?>    
                <article class="message details-section-main">
                    <div class="message-body">
                        <div class="container-row-free">
                            <?php if (!empty($property["type_of_terrain"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Tipo de terreno</h3>
                                <span class="text-span"><?= $property["type_of_terrain"][0]["name"] ?></span>
                            </div>
                            <?php } ?>
                            <?php if (!empty($property["wheeled_access"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Acceso rodado</h3>
                                <span class="text-span"><?= $property["wheeled_access"][0]["name"] ?></span>
                            </div>
                            <?php } ?>
                            <?php if (!empty($property["nearest_municipality_distance"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Distancia municipio más cercano</h3>
                                <span class="text-span"><?= $property["nearest_municipality_distance"][0]["name"] ?></span>
                            </div>
                            <?php } ?>
                            <?php if (!empty($property["land_size"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Superficie total</h3>
                                <span class="text-span"><?= $property["land_size"] ?> m<sup>2</sup></span>
                            </div>
                            <?php } ?>


                            <?php if (!empty($property["typology"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Tipología</h3>
                                <span class="text-span"><?= $property["typology"][0]["name"] ?></span>
                            </div>
                            <?php } ?>
                            <?php if (!empty($property["community_expenses"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Gastos de comunidad</h3>
                                <span class="text-span"><?= $property["community_expenses"] ?> €</span>
                            </div>
                            <?php } ?>
                            <?php if (!empty($property["ibi"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">IBI</h3>
                                <span class="text-span"><?= $property["ibi"] ?> €</span>
                            </div>
                            <?php } ?>
                            <?php if (!empty($property["mortgage_rate"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Hipoteca</h3>
                                <span class="text-span"><?= $property["mortgage_rate"] ?> €</span>
                            </div>
                            <?php } ?>
                            <?php if (!empty($property["reason_for_sale"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Situación de venta</h3>
                                <span class="text-span"><?= $property["reason_for_sale"][0]["name"] ?></span>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </article>
            <?php
                }
            ?>
            <?php
                $hasMainDetailRows = !empty($property["plaza_capacity"])
                    || !empty($property["has_tenants"])
                    || !empty($property["bank_owned_property"])
                    || !empty($property["useful_meters"])
                    || !empty($property["plot_meters"])
                    || !empty($property["number_of_plants"])
                    || !empty($property["bathrooms"])
                    || !empty($property["bedrooms"])
                    || !empty($property["linear_meters_of_facade"])
                    || !empty($property["stays"])
                    || !empty($property["number_of_shop_windows"])
                    || !empty($property["year_of_construction"])
                    || !empty($property["type_heating"])
                    || !empty($property["orientations"])
                    || !empty($property["elevator"]);
                if ($hasMainDetailRows){
            ?>
                <article class="message details-section-main">
                    <div class="message-body">
                        <div class="container-row-free">
                            <?php if (!empty($property["plaza_capacity"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Capacidad de la plaza</h3>
                                <span class="text-span"><?= $property["plaza_capacity"][0]["name"] ?></span>
                            </div>
                            <?php } ?>            
                            <?php if (!empty($property["has_tenants"]) && empty($showHasTenantsTopCard)){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Inquilinos</h3>
                                <?php if (strval($property["has_tenants"]) === "1"){
                                    echo "<span class='text-span'>Si tiene</span>";
                                }else if (strval($property["has_tenants"]) === "2"){
                                    echo "<span class='text-span'>No tiene</span>";
                                }else{
                                    echo "<span class='text-span'>Preguntar</span>";
                                } ?>
                            </div>
                            <?php } ?>            


                            <?php if (!empty($property["bank_owned_property"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Es un inmueble del banco</h3>
                            </div>
                            <?php } ?>
                            <?php if (!empty($property["useful_meters"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">M<sup>2</sup> útiles</h3>
                                <span class="text-span"><?= $property["useful_meters"] ?> m<sup>2</sup></span>
                            </div>
                            <?php } ?>
                            <?php if (!empty($property["plot_meters"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">M<sup>2</sup> parcela</h3>
                                <span class="text-span"><?= $property["plot_meters"] ?> m<sup>2</sup></span>
                            </div>
                            <?php } ?>
                            <?php if (!empty($property["number_of_plants"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Plantas de la casa o chalet</h3>
                                <span class="text-span"><?= $property["number_of_plants"] ?></span>
                            </div>
                            <?php } ?>
                            <?php if (!empty($property["bathrooms"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Número de baños y aseo</h3>
                                <span class="text-span"><?= $property["bathrooms"] ?></span>
                            </div>
                            <?php } ?>
                            <?php if (!empty($property["bedrooms"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Número de dormitorios</h3>
                                <span class="text-span"><?= $property["bedrooms"] ?></span>
                            </div>
                            <?php } ?>


                            <?php if (!empty($property["linear_meters_of_facade"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Metros lineales de fachada</h3>
                                <span class="text-span"><?= $property["linear_meters_of_facade"] ?></span>
                            </div>
                            <?php } ?>
                            <?php if (!empty($property["stays"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Estancias</h3>
                                <span class="text-span"><?= $property["stays"] ?></span>
                            </div>
                            <?php } ?>
                            <?php if (!empty($property["number_of_shop_windows"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Número de escaparates</h3>
                                <span class="text-span"><?= $property["number_of_shop_windows"] ?></span>
                            </div>
                            <?php } ?>
                            



                            <?php if (!empty($property["year_of_construction"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Año de construcción</h3>
                                <span class="text-span"><?= $property["year_of_construction"] ?></span>
                            </div>
                            <?php } ?>
                            <?php if (!empty($property["type_heating"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Tipo de calefacción</h3>
                                <span class="text-span"><?= $property["type_heating"][0]["name"] ?></span>
                            </div>
                            <?php } ?>
                            <?php if (!empty($property["orientations"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Orientación</h3>
                                <span class="text-span">
                                <?php
                                    $crows = 1;
                                    foreach($property["orientations"] as $o){
                                        echo $o["name"];
                                        if (count($property["orientations"])>1){
                                            if (count($property["orientations"]) == $crows){
                                                echo ".";
                                            }else{
                                                echo ", ";
                                            }
                                        }
                                        $crows += 1;
                                    }
                                ?>
                                </span>
                            </div>
                            <?php } ?>

                            <?php if (!empty($property["elevator"])){ ?>
                            <div class="box-li">
                                <h3 class="text-title-h">Tiene ascensor</h3>
                                <span class="text-span">
                                    <?php if (!empty($property["wheelchair_accessible_elevator"])){
                                        echo "<span class='span-value'>Apto para silla de ruedas.</span>";
                                    }else{
                                        echo "<span class='span-value'>No apto para silla de ruedas.</span>";
                                    } ?>
                                </span>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </article>
            <?php } ?>
            <?php
                $hasDetailsSideStack = !empty($property["features"])
                    || ((int) ($property["type_id"] ?? 0) === 9 && !empty($property["terrain_qualifications"]))
                    || !empty($property["equipments"])
                    || !empty($property["power_consumption_rating"]);
            ?>
            <?php if ($hasDetailsSideStack){ ?>
                <div class="details-side-stack">
            <?php } ?>
            <?php if (!empty($property["features"])){ ?>
                <article class="message details-section-features">
                    <div class="message-header">
                        <p>Características básicas</p>
                    </div>
                    <div class="message-body">
                        <div class="container-row">
                            <ul>
                                <?php foreach($property["features"] as $feature){ ?>
                                    <li><?= $feature["name"] ?></li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                </article>
            <?php } ?>
            <?php if ((int) ($property["type_id"] ?? 0) === 9 && !empty($property["terrain_qualifications"])) { ?>
                <article class="message details-section-features">
                    <div class="message-header">
                        <p>Tipo de calificación</p>
                    </div>
                    <div class="message-body">
                        <div class="container-row">
                            <ul>
                                <?php foreach($property["terrain_qualifications"] as $terrainQualification){ ?>
                                    <li><?= $terrainQualification["name"] ?></li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                </article>
            <?php } ?>
            <?php if (!empty($property["equipments"])){ ?>
                <article class="message details-section-equipments">
                    <div class="message-header">
                        <p>Equipamientos</p>
                    </div>
                    <div class="message-body">
                        <div class="container-row">
                            <ul>
                                <?php foreach($property["equipments"] as $feature){ ?>
                                    <li><?= $feature["name"] ?></li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                </article>
            <?php } ?>
            <?php if (!empty($property["power_consumption_rating"])){ ?>
                <article class="message details-section-energy">
                    <div class="message-header">
                        <p>Certificado energético</p>
                    </div>
                    <div class="message-body">
                        <div class="container-row">
                            <?php if (!empty($property["power_consumption_rating_id"]) || !empty($property["energy_consumption"])){ ?>
                                <div class="container-row-icon-rating">
                                    <span>Consumo de energía</span>
                                    <div class="container-value">
                                        <?php 
                                        if (intval($property["power_consumption_rating_id"]) === 1 || intval($property["power_consumption_rating_id"]) === 9 || intval($property["power_consumption_rating_id"]) === 10 ){     
                                            echo  "<span class='tag is-link is-light'>".$property["power_consumption_rating"][0]["name"]."</span>";
                                        }else if (intval($property["power_consumption_rating_id"]) === 2){
                                            echo "<img src='".base_url("img/icons/flecha_1.png")."' alt='' class='img-icon-p-c-rating'>";
                                        }else if (intval($property["power_consumption_rating_id"]) === 3){
                                            echo "<img src='".base_url("img/icons/flecha_2.png")."' alt='' class='img-icon-p-c-rating'>";
                                        }else if (intval($property["power_consumption_rating_id"]) === 4){
                                            echo "<img src='".base_url("img/icons/flecha_3.png")."' alt='' class='img-icon-p-c-rating'>";
                                        }else if (intval($property["power_consumption_rating_id"]) === 5){
                                            echo "<img src='".base_url("img/icons/flecha_4.png")."' alt='' class='img-icon-p-c-rating'>";
                                        }else if (intval($property["power_consumption_rating_id"]) === 6){
                                            echo "<img src='".base_url("img/icons/flecha_5.png")."' alt='' class='img-icon-p-c-rating'>";
                                        }else if (intval($property["power_consumption_rating_id"]) === 7){
                                            echo "<img src='".base_url("img/icons/flecha_6.png")."' alt='' class='img-icon-p-c-rating'>";
                                        }else if (intval($property["power_consumption_rating_id"]) === 8){
                                            echo "<img src='".base_url("img/icons/flecha_7.png")."' alt='' class='img-icon-p-c-rating'>";
                                        }else{
                                            echo "";
                                        }
                                        ?>
                                        <?php if (!empty($property["energy_consumption"])){ ?>
                                            <span><?= $property["energy_consumption"] ?> kWh/m² año</span>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if (!empty($property["emissions_rating_id"]) || !empty($property["emissions_consumption"])){ ?>
                                <div class="container-row-icon-rating">
                                    <span>Consumo de emisiones</span>
                                    <div class="container-value">
                                        <?php 
                                        if (intval($property["emissions_rating_id"]) === 1){
                                            echo "<img src='".base_url("img/icons/flecha_1.png")."' alt='' class='img-icon-p-c-rating'>";
                                        }else if (intval($property["emissions_rating_id"]) === 2){
                                            echo "<img src='".base_url("img/icons/flecha_2.png")."' alt='' class='img-icon-p-c-rating'>";
                                        }else if (intval($property["emissions_rating_id"]) === 3){
                                            echo "<img src='".base_url("img/icons/flecha_3.png")."' alt='' class='img-icon-p-c-rating'>";
                                        }else if (intval($property["emissions_rating_id"]) === 4){
                                            echo "<img src='".base_url("img/icons/flecha_4.png")."' alt='' class='img-icon-p-c-rating'>";
                                        }else if (intval($property["emissions_rating_id"]) === 5){
                                            echo "<img src='".base_url("img/icons/flecha_5.png")."' alt='' class='img-icon-p-c-rating'>";
                                        }else if (intval($property["emissions_rating_id"]) === 6){
                                            echo "<img src='".base_url("img/icons/flecha_6.png")."' alt='' class='img-icon-p-c-rating'>";
                                        }else if (intval($property["emissions_rating_id"]) === 7){
                                            echo "<img src='".base_url("img/icons/flecha_7.png")."' alt='' class='img-icon-p-c-rating'>";
                                        }else{
                                            echo "";
                                        }
                                        ?>
                                        <?php if (!empty($property["emissions_consumption"])){ ?>
                                            <span><?= $property["emissions_consumption"] ?> kgCO/m² año</span>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </article>
            <?php } ?>
            <?php if ($hasDetailsSideStack){ ?>
                </div>
            <?php } ?>
            <?php if (!empty($property["interior_wheelchair"]) || !empty($property["outdoor_wheelchair"])){ ?>
                <article class="message details-section-accessibility">
                    <div class="message-header">
                        <p>Adaptado a personas con movilidad reducida</p>
                    </div>
                    <div class="message-body">
                        <div class="container-row">
                            <ul>
                                <?php if (!empty($property["interior_wheelchair"])){
                                    echo "<li>El acceso exterior a la vivienda está adaptada para silla de ruedas</li>";
                                }else{
                                    echo "<li>El acceso exterior a la vivienda <span class='tag is-danger'>NO</span> está adaptada para silla de ruedas</li>";
                                } if (!empty($property["outdoor_wheelchair"])){ 
                                    echo "<li>El interior de la vivienda está adaptada para silla de ruedas</li>";
                                } else{
                                    echo "<li>El interior de la vivienda <span class='tag is-danger'>NO</span> está adaptada para silla de ruedas</li>";
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </article>
            <?php } ?>
        </div>
    </div>
    
