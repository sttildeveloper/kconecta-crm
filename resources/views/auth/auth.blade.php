<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Kconecta - {{ $mode === 'register' ? 'Crear cuenta' : 'Iniciar sesion' }}</title>
        <link rel="stylesheet" href="{{ asset('css/page/login.css') }}">
        <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
    </head>
    <body>
        @if ($errors->any() || session('status'))
            <div class="message">
                @if ($errors->any())
                    <div class="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if (session('status'))
                    <div class="alert success">{{ session('status') }}</div>
                @endif
            </div>
        @endif

        <div class="container-main {{ $mode === 'register' ? 'right-panel-active' : '' }}" id="container-main">
            <div class="form-container sign-up-container">
                <form method="POST" action="{{ route('register') }}">
                    @csrf
                    <input type="hidden" name="registration_form_started_at" value="{{ $registrationFormStartedAt ?? '' }}">
                    <div style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
                        <label for="website">Sitio web</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    <div class="container-logo-image-dml-redirect-start">
                        <a href="{{ url('/') }}">
                            <img src="{{ asset('img/kconecta_icon.webp') }}" alt="Kconecta">
                        </a>
                    </div>
                    <h1 class="title-main-section">Crea tu Cuenta</h1>

                    <label>
                        <span>Tipo de usuario *</span>
                        <select id="user_level_id" name="user_level_id" required>
                            <option value="">Seleccione</option>
                            @foreach ($userLevels as $level)
                                <option value="{{ $level->id }}" {{ (string) old('user_level_id') === (string) $level->id ? 'selected' : '' }}>
                                    {{
                                        (int) $level->id === \App\Models\User::LEVEL_SERVICE_PROVIDER
                                            ? 'Proveedor de servicios'
                                            : ((int) $level->id === \App\Models\User::LEVEL_AGENT
                                                ? 'Agente inmobiliario'
                                                : ((int) $level->id === \App\Models\User::LEVEL_FINAL_CLIENT
                                                    ? 'Cliente final'
                                                    : $level->name))
                                    }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <input type="hidden" name="document_type" value="">
                    <input type="hidden" name="document_number" value="">

                    <div id="company_name_row" class="full-width-row">
                        <label>
                            <span>Raz&oacute;n social (Opcional)</span>
                            <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}">
                        </label>
                    </div>

                    <div class="container-two-col" id="person_name_row">
                        <label>
                            <span>Nombre (Opcional)</span>
                            <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}">
                        </label>
                        <label>
                            <span>Apellido (Opcional)</span>
                            <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}">
                        </label>
                    </div>

                    <div class="container-two-col">
                        <label>
                            <span id="phone_label">M&oacute;vil (WhatsApp) (Opcional)</span>
                            <input type="text" id="phone_input" name="phone" value="{{ old('phone') }}">
                        </label>
                        <label>
                            <span>Tel&eacute;fono fijo (Opcional)</span>
                            <input type="text" name="landline_phone" value="{{ old('landline_phone') }}">
                        </label>
                    </div>

                    <label>
                        <span>E-mail *</span>
                        <input type="email" name="email" value="{{ old('email') }}" required>
                    </label>

                    <div class="container-two-col">
                        <label>
                            <span>Contrase&ntilde;a *</span>
                            <div class="input-with-tools">
                                <input type="password" id="password" name="password" required>
                                <div class="input-tools">
                                    <button type="button" class="input-tool" data-password-toggle="password" aria-label="Mostrar u ocultar contraseña">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/>
                                            <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="input-tool" data-password-generate="1" aria-label="Generar contraseña segura">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 10V7a6 6 0 0 1 12 0v3"/>
                                            <rect x="4" y="10" width="16" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/>
                                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 14v2"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </label>
                        <label>
                            <span>Repita la contrase&ntilde;a *</span>
                            <div class="input-with-tools">
                                <input type="password" id="password_confirmation" name="password_confirmation" required>
                                <div class="input-tools">
                                    <button type="button" class="input-tool" data-password-toggle="password_confirmation" aria-label="Mostrar u ocultar confirmación de contraseña">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/>
                                            <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="input-tool" data-password-generate="1" aria-label="Generar contraseña segura">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 10V7a6 6 0 0 1 12 0v3"/>
                                            <rect x="4" y="10" width="16" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/>
                                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 14v2"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </label>
                    </div>

                    <button type="submit" id="lila">Registrar</button>
                    <span class="span-redirect-page" onclick="window.location='{{ route('login') }}'">
                        &iquest;Ya tienes cuenta? Inicia sesi&oacute;n
                    </span>
                </form>
            </div>

            <div class="form-container sign-in-container">
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="container-logo-image-dml-redirect-start">
                        <a href="{{ url('/') }}">
                            <img src="{{ asset('img/kconecta_icon.webp') }}" alt="Kconecta">
                        </a>
                    </div>
                    <h1 class="title-main-section">Iniciar Sesi&oacute;n</h1>

                    <label>
                        <span>E-mail</span>
                        <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                    </label>
                    <label>
                        <span>Contrase&ntilde;a</span>
                        <input type="password" name="password" required autocomplete="current-password">
                    </label>

                    <div class="container-checkbox-remember-session">
                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label for="remember"><span>Recordar sesi&oacute;n</span></label>
                    </div>

                    @if (isset($canResetPassword) && $canResetPassword)
                        <a href="{{ route('password.request') }}">Olvidaste tu contrase&ntilde;a?</a>
                    @endif
                    <button type="submit" id="lila">Iniciar Sesi&oacute;n</button>
                    <span class="span-redirect-page" onclick="window.location='{{ route('register') }}'">
                        &iquest;No tienes cuenta? Reg&iacute;strate
                    </span>
                </form>
            </div>

            <div class="overlay-container">
                <div class="overlay">
                    <div class="overlay-panel overlay-left">
                        <h1>&iexcl;Bienvenido!</h1>
                        <p>Inicia sesi&oacute;n con tu cuenta</p>
                        <button class="ghost" type="button" onclick="window.location='{{ route('login') }}'">
                            Inicia sesi&oacute;n
                        </button>
                    </div>
                    <div class="overlay-panel overlay-right">
                        <h1>Hola!!!</h1>
                        <p>Crear tu cuenta</p>
                        <button class="ghost" type="button" onclick="window.location='{{ route('register') }}'">
                            Registrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @if (!empty($mapsKey))
            <script src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&libraries=places"></script>
        @endif
        <script>
            (() => {
                const companyRow = document.getElementById('company_name_row');
                const personRow = document.getElementById('person_name_row');
                const userLevelSelect = document.getElementById('user_level_id');
                const companyName = document.getElementById('company_name');
                const firstName = document.getElementById('first_name');
                const lastName = document.getElementById('last_name');
                const phoneInput = document.getElementById('phone_input');
                const phoneLabel = document.getElementById('phone_label');
                const passwordInput = document.getElementById('password');
                const passwordConfirmationInput = document.getElementById('password_confirmation');

                const syncDocumentTypeFields = () => {
                    if (companyRow) {
                        companyRow.style.display = '';
                    }
                    if (personRow) {
                        personRow.style.display = '';
                    }

                    if (companyName) {
                        companyName.required = false;
                    }
                    if (firstName) {
                        firstName.required = false;
                    }
                    if (lastName) {
                        lastName.required = false;
                    }

                    if (phoneInput) {
                        phoneInput.required = false;
                    }
                    if (phoneLabel) {
                        phoneLabel.innerHTML = 'M&oacute;vil (WhatsApp) (Opcional)';
                    }
                };

                syncDocumentTypeFields();
                userLevelSelect?.addEventListener('change', syncDocumentTypeFields);

                const createSecurePassword = (length = 14) => {
                    const lower = 'abcdefghijkmnopqrstuvwxyz';
                    const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
                    const digits = '23456789';
                    const symbols = '!@#$%*+-_?';
                    const all = lower + upper + digits + symbols;

                    const pick = (pool) => pool[Math.floor(Math.random() * pool.length)];
                    const passwordChars = [
                        pick(lower),
                        pick(upper),
                        pick(digits),
                        pick(symbols),
                    ];

                    while (passwordChars.length < length) {
                        passwordChars.push(pick(all));
                    }

                    for (let i = passwordChars.length - 1; i > 0; i -= 1) {
                        const j = Math.floor(Math.random() * (i + 1));
                        const tmp = passwordChars[i];
                        passwordChars[i] = passwordChars[j];
                        passwordChars[j] = tmp;
                    }

                    return passwordChars.join('');
                };

                const applyGeneratedPassword = () => {
                    const generated = createSecurePassword();
                    if (passwordInput) {
                        passwordInput.value = generated;
                        passwordInput.type = 'text';
                    }
                    if (passwordConfirmationInput) {
                        passwordConfirmationInput.value = generated;
                        passwordConfirmationInput.type = 'text';
                    }
                };

                document.querySelectorAll('[data-password-toggle]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const targetId = button.getAttribute('data-password-toggle');
                        const target = targetId ? document.getElementById(targetId) : null;
                        if (!target) {
                            return;
                        }
                        target.type = target.type === 'password' ? 'text' : 'password';
                    });
                });

                document.querySelectorAll('[data-password-generate]').forEach((button) => {
                    button.addEventListener('click', applyGeneratedPassword);
                });

            })();
        </script>
    </body>
</html>
