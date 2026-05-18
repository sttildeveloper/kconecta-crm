<div class="modal" id="googleLoginModal">
    <div class="modal-background"></div>
    <div class="modal-content box" style="width: 100%; max-width: 26rem">
        <div class="ctn-sendMessageEmail container is-fullwidth" style="display:none;">
            <div class="columns is-centered is-fullwidth">
                <div class="column is-fullwidth">
                    <h1 class="title is-4 has-text-centered" style="margin-bottom: 32px;">Enviar Mensaje</h1>
                    <form action="#" id="formSendMessageEmail" class="is-fullwidth">
                        <input type="hidden" name="provider_email">
                        <input type="hidden" name="property_link">
                        <input type="hidden" name="user_email">
                        <input type="hidden" name="user_name">
                        <input type="hidden" name="property_id" value="<?= $property["id"] ?>">
                        <div class="field">
                            <div class="control">
                                <textarea name="message" id="messageEmailToProvider" class="textarea is-medium" placeholder="Escribe tu mensaje aquí..."></textarea>
                            </div>
                        </div>
                        <div class="control">
                            <button type="button" id="btnSubitSendMessageToProvider" class="button is-primary is-fullwidth has-text-white A7x9Vb2QmL-psu" data-i="<?= $property["id"] ?>" data-col="email_owner">Enviar Mensaje</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="ctn-googleSignInButton is-fullwidth" style="display:none;">
            <h2 style="margin-bottom: 1rem;">Inicia sesión con Google</h2>
            <div id="googleSignInButton"></div>
            <div id="loginStatus" style="margin-top:10px;"></div>
        </div>
    </div>
    <button class="button modal-close"></button>
</div>
<div class="modal" id="modal-share">
    <div class="modal-background"></div>
    <div class="modal-content">
        <div class="box p-5">
            <div class="is-clearfix">
                <h3 class="title is-4 is-pulled-left has-text-grey-darker">Compartir anuncio</h3>
                <button class="delete is-large is-pulled-right" onclick="closeModal(document.getElementById('modal-share'))" aria-label="close"></button>
            </div>
            <p class="subtitle is-6 mb-2 has-text-grey"><?= $property["title"] ?></p>
            <p class="subtitle is-6 has-text-weight-bold has-text-primary"><?php
                    if (intval($property["category_id"]) == 1){
                        echo ($property["rental_price"]);
                    }else if (intval($property["category_id"]) == 2){
                        echo ($property["sale_price"]);
                    }else{
                        echo "";
                    }
                    ?> €
            </p>

            <hr class="has-background-light">

            <div class="field mb-5">
                <label class="label has-text-grey-dark">Compartir por redes sociales</label>
                <div class="control">
                    <a style="display:flex;column-gap:.4rem;" class="button is-success is-outlined is-fullwidth is-rounded A7x9Vb2QmL-psu" data-i="<?= $property["id"] ?>" data-col="whatsapp_clicks" href="https://wa.me/?text=Estimado/a, comparto con usted los detalles de una propiedad que podría resultar de interésult/<?=$property["reference"] ?>" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24"><path fill="#65a30d" fill-rule="evenodd" d="M12 1.25c5.937 0 10.75 4.813 10.75 10.75S17.937 22.75 12 22.75c-1.86 0-3.61-.473-5.137-1.305l-4.74.795a.75.75 0 0 1-.865-.852l.8-5.29A10.7 10.7 0 0 1 1.25 12C1.25 6.063 6.063 1.25 12 1.25M7.943 6.7c-.735 0-1.344.62-1.23 1.386c.216 1.436.854 4.082 2.752 5.994c1.984 1.999 4.823 2.854 6.36 3.191c.796.175 1.475-.455 1.475-1.232v-1.824a.3.3 0 0 0-.192-.28l-1.96-.753a.3.3 0 0 0-.166-.014l-1.977.386c-1.275-.66-2.047-1.4-2.51-2.515l.372-2.015a.3.3 0 0 0-.014-.16l-.735-1.969a.3.3 0 0 0-.28-.195z" clip-rule="evenodd"/></svg>
                        Enviar por Whatsapp
                    </a>
                </div>
                <div class="control mt-3">
                    <button style="display:flex;column-gap:.4rem;" class="button is-success is-outlined is-fullwidth is-rounded A7x9Vb2QmL-psu" data-i="<?= $property["id"] ?>" data-col="shared_facebook" id="btn-share-facebook" data-url="https://kconecta.com/result/<?=$property["reference"] ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 256 256"><path fill="#1877F2" d="M256 128C256 57.308 198.692 0 128 0S0 57.308 0 128c0 63.888 46.808 116.843 108 126.445V165H75.5v-37H108V99.8c0-32.08 19.11-49.8 48.348-49.8C170.352 50 185 52.5 185 52.5V84h-16.14C152.959 84 148 93.867 148 103.99V128h35.5l-5.675 37H148v89.445c61.192-9.602 108-62.556 108-126.445"/><path fill="#FFF" d="m177.825 165l5.675-37H148v-24.01C148 93.866 152.959 84 168.86 84H185V52.5S170.352 50 156.347 50C127.11 50 108 67.72 108 99.8V128H75.5v37H108v89.445A129 129 0 0 0 128 256a129 129 0 0 0 20-1.555V165z"/></svg>
                        Publicar en facebook
                    </button>
                </div>
            </div>

            <hr class="has-background-light">

            <div class="field mb-5">
                <label class="label has-text-grey-dark">Copiar enlace</label>
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <input class="input is-rounded" id="link-reference" type="text" value="<?= base_url("result/").$property["reference"] ?>" readonly>
                    </div>
                    <div class="control">
                        <button class="button is-info is-rounded A7x9Vb2QmL-psu" data-i="<?= $property["id"] ?>" data-col="link_copied" id="copyLinkButton">Copiar</button>
                    </div>
                </div>
            </div>

            <hr class="has-background-light">

            <div class="field mb-5">
                <label class="label has-text-grey-dark">Compartir por email</label>
                <div class="control">
                    <input class="input is-rounded" type="email" placeholder="Email de tus amigos" id="input-email-share">
                </div>
                <p class="help has-text-grey-light">Si son varios sepáralos con una coma (,)</p>
            </div>

            <div class="field is-grouped is-grouped-right">
                <div class="control">
                    <button class="button is-primary is-rounded A7x9Vb2QmL-psu" data-i="<?= $property["id"] ?>" data-col="shared_friends" id="send-share-to-emails">Enviar</button>
                </div>
            </div>
        </div>
    </div>
    <button class="button modal-close"></button>
</div>

<script>
    const btn_share = document.getElementById("btn-share");
    btn_share.addEventListener("click", ()=>{
        openModal(document.getElementById("modal-share"));
    })
    const sendShareToEmails = document.getElementById("send-share-to-emails");
    const inputEmailShare = document.getElementById("input-email-share");
    const propertyLink = document.getElementById("link-reference");
    sendShareToEmails.addEventListener("click", async()=>{
        if (inputEmailShare.value.trim() !== ""){
            sendShareToEmails.textContent = "Enviando...";
            sendShareToEmails.setAttribute("disabled", true);
            await fetch("/api/send/message/email_share?user_emails="+inputEmailShare.value+"&property_link="+propertyLink.value).then(res => res.json()).then(data => {
                inputEmailShare.value = "";
                closeModal(document.getElementById("modal-share"));
                sendShareToEmails.textContent = "Enviar";
                sendShareToEmails.removeAttribute("disabled");
            })
        }
    });


    const btn_share_facebook = document.getElementById("btn-share-facebook");
    btn_share_facebook.addEventListener("click", ()=>{
        const url = btn_share_facebook.dataset.url;
        const facebookUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
        window.open(facebookUrl, '_blank');
    })
    document.getElementById('copyLinkButton').addEventListener('click', function() {
        const linkInput = this.closest('.field').querySelector('input');
        linkInput.select();
        linkInput.setSelectionRange(0, 99999);
        document.execCommand("copy");

        const originalText = this.textContent;
        this.textContent = '¡Copiado!';
        setTimeout(() => {
            this.textContent = originalText;
        }, 2000);
    });
</script>
<script src="https://accounts.google.com/gsi/client" async defer></script>

<script>
    const formSendMessageEmail = document.getElementById("formSendMessageEmail");
    const ctn_sendMessageEmail = document.querySelector(".ctn-sendMessageEmail");
    const ctn_googleSignInButton = document.querySelector(".ctn-googleSignInButton");

    let googleAuthInstance;

    function openLoginModal() {
        const details_userfree = localStorage.getItem("userfree");
        if (details_userfree){
            const json_userfree = JSON.parse(details_userfree);
            ctn_googleSignInButton.style.display = "none";
            ctn_sendMessageEmail.removeAttribute("style");

            document.getElementsByName("user_email")[0].value = json_userfree.user.email;
            document.getElementsByName("user_name")[0].value = json_userfree.user.name;
        }else{
            ctn_sendMessageEmail.style.display = "none";
            ctn_googleSignInButton.removeAttribute("style");
            initializeGoogleAuth();
        }
        openModal(document.getElementById("googleLoginModal"));
    }

    function initializeGoogleAuth() {
        google.accounts.id.initialize({
            client_id: "916285583768-enj3t3n6c9esrggsn8giik6j541kbcjg.apps.googleusercontent.com",
            callback: handleCredentialResponse,
            ux_mode: 'popup',
            context: 'use'
        });
        
        google.accounts.id.renderButton(
            document.getElementById("googleSignInButton"),{ 
                theme: "outline", 
                size: "large",
            }
        );
        
        google.accounts.id.prompt();
    }

    function handleCredentialResponse(response) {
        const loginStatus = document.getElementById('loginStatus');
        loginStatus.innerHTML = 'Verificando credenciales...';
        const form_data = new FormData();
        form_data.append("credential", response.credential);
        fetch('/api/google/user/verify_token_google', {
            method: 'POST',
            body: form_data,
        })
        .then(response => response.json())
        .then(data => {
            localStorage.setItem("userfree", JSON.stringify(data));
            const ctn_profile = document.querySelector(".container-profile-userfree-app");
            ctn_profile.classList.add("container-profile-userfree-app-active");
            const img_tag = document.createElement("img");
            img_tag.src = data.user.picture;
            img_tag.alt = "profile user";
            ctn_profile.insertAdjacentElement("beforeend", img_tag);

            ctn_googleSignInButton.style.display = "none";
            ctn_sendMessageEmail.removeAttribute("style");
            document.querySelector(".a-loggin-redirect").style.display = "none";

            document.getElementsByName("user_email")[0].value = data.user.email;
            document.getElementsByName("user_name")[0].value = data.user.name;
        })
        .catch(error => {
            loginStatus.innerHTML = 'Error en la conexión';
            console.error('Error:', error);
        });
    }
    
    const btn_open_login_modal = document.getElementById("btn-openLoginModal");
    btn_open_login_modal.addEventListener("click", ()=>{
        document.getElementsByName("provider_email")[0].value = btn_open_login_modal.dataset.email;
        document.getElementsByName("property_link")[0].value = btn_open_login_modal.dataset.link;
        openLoginModal();
    })
    const btn_send_message_email = document.getElementById("btnSubitSendMessageToProvider");
    const messageEmailToProvider = document.getElementById("messageEmailToProvider")
    btn_send_message_email.addEventListener("click", async()=>{
        btn_send_message_email.textContent = "Enviando...";
        btn_send_message_email.setAttribute("disabled", true);
        if (messageEmailToProvider){
            const form_data = new FormData(formSendMessageEmail);
            await fetch("/api/send/message/email_to_provider", {
                method: "POST",
                body: form_data,
            }).then(res => res.text()).then(data =>{
                btn_send_message_email.textContent = "Enviar Mensaje";
                btn_send_message_email.removeAttribute("disabled");
                closeModal(document.getElementById("googleLoginModal"));
                document.getElementsByName("message")[0].value = "";
            });
        }else{
            alert("El mensaje no debe estar vacio.");
        }

    })
</script>


