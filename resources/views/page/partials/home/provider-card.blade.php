<article
    class="home-provider-card"
    data-provider-id="{{ $provider['id'] }}"
    data-provider-latitude="{{ $provider['latitude'] }}"
    data-provider-longitude="{{ $provider['longitude'] }}"
>
    <a class="home-provider-card__identity" href="{{ $provider['profile_url'] }}">
        <img src="{{ $provider['photo_url'] }}" alt="" width="76" height="76" loading="lazy">
        <span>
            <strong>{{ $provider['name'] }}</strong>
            <small>{{ ! empty($provider['specialties']) ? implode(' · ', $provider['specialties']) : 'Profesional de servicios' }}</small>
            @if ($provider['ratings_count'] > 0)
                <span class="home-provider-card__rating" aria-label="{{ $provider['average_stars'] }} de 5, {{ $provider['ratings_count'] }} valoraciones">
                    <span aria-hidden="true">★</span> {{ number_format($provider['average_stars'], 1, ',', '.') }}
                    <small>({{ $provider['ratings_count'] }})</small>
                </span>
            @else
                <span class="home-provider-card__rating home-provider-card__rating--empty">Sin valoraciones</span>
            @endif
        </span>
    </a>
    <div class="home-provider-card__zone">
        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 21s7-5.6 7-12A7 7 0 1 0 5 9c0 6.4 7 12 7 12Zm0-9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
        <span>{{ $provider['zone'] }}</span>
        <strong class="home-provider-card__distance" data-provider-distance hidden></strong>
    </div>
    <div class="home-provider-card__actions">
        @if ($provider['phone_url'])
            <a href="{{ $provider['phone_url'] }}">
                <span aria-hidden="true">☎</span> Llamar
            </a>
        @endif
        @if ($provider['email'])
            <a href="mailto:{{ $provider['email'] }}">
                <span aria-hidden="true">✉</span> E-mail
            </a>
        @endif
        @if (! $provider['phone_url'] && ! $provider['email'])
            <a href="{{ $provider['profile_url'] }}">Ver perfil</a>
        @endif
    </div>
</article>
