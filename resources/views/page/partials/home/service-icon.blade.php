@switch($icon)
    @case('plumbing')
        <svg class="home-service-card__icon" aria-hidden="true" viewBox="0 0 64 64"><path d="M11 12h18v12H11zm9 12v11m0 0h24m0 0v7m-7 0h14v10H37zM44 35V21m-6 0h12M44 8v13"/></svg>
        @break
    @case('brick')
        <svg class="home-service-card__icon" aria-hidden="true" viewBox="0 0 64 64"><path d="M7 45h50v11H7zm5-14h18v11H12zm22 0h18v11H34zM7 17h18v11H7zm22 0h18v11H29zM49 8l7 7M49 15l7-7"/></svg>
        @break
    @case('wood')
        <svg class="home-service-card__icon" aria-hidden="true" viewBox="0 0 64 64"><path d="m9 39 34-28 12 14-34 29-12-7Zm9 10 34-29M25 43l-7-8m18-1-7-8m18-1-7-8"/></svg>
        @break
    @case('key')
        <svg class="home-service-card__icon" aria-hidden="true" viewBox="0 0 64 64"><path d="M38 27a14 14 0 1 1 7 12l-9 9h-7v7h-8v-8l14-14a14 14 0 0 1 3-6Zm8 0h.1"/></svg>
        @break
    @case('bolt')
        <svg class="home-service-card__icon" aria-hidden="true" viewBox="0 0 64 64"><path d="M35 5 16 36h15l-3 23 20-34H33l2-20Z"/></svg>
        @break
    @case('broom')
        <svg class="home-service-card__icon" aria-hidden="true" viewBox="0 0 64 64"><path d="m42 8-9 31M30 37l8 3c7 3 10 10 8 16H12c2-11 8-18 18-19Zm-12 7 5 12m6-16 3 16"/></svg>
        @break
    @case('truck')
        <svg class="home-service-card__icon" aria-hidden="true" viewBox="0 0 64 64"><path d="M6 16h32v30H6zm32 12h10l10 11v7H38zM17 53a6 6 0 1 0 0-12 6 6 0 0 0 0 12Zm30 0a6 6 0 1 0 0-12 6 6 0 0 0 0 12Z"/></svg>
        @break
    @case('paint')
        <svg class="home-service-card__icon" aria-hidden="true" viewBox="0 0 64 64"><path d="M12 11h38v15H12zm19 15v8h12v8H31v14M8 15h4m38 0h6v19H43"/></svg>
        @break
    @default
        <svg class="home-service-card__icon" aria-hidden="true" viewBox="0 0 64 64"><path d="m8 30 24-21 24 21M14 27v29h36V27M25 56V39h14v17M42 18l7-6 5 6M18 41l6-6 6 6"/></svg>
@endswitch
