{{--
    Panel-wide identity layer. Injected at panels::head.end with no scope, so
    it covers every admin screen. Nothing in vendor/ is touched and no Filament
    blade is overridden — Filament's own utilities are equally specific, hence
    the !important on most declarations.
--}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

<style>
    :root {
        --mo-ground: #08090a;
        --mo-panel: #101214;
        --mo-panel-raised: #16191b;
        --mo-ink: #f3f3f3;
        --mo-ink-muted: rgba(243, 243, 243, 0.58);
        --mo-line: rgba(243, 243, 243, 0.10);
        --mo-ember: #d1552e;
        --mo-display: 'Oswald', 'Arial Narrow', sans-serif;
        --mo-ui: 'Inter', ui-sans-serif, system-ui, sans-serif;
    }

    .fi-body,
    .fi-simple-layout {
        background: var(--mo-ground) !important;
        color: var(--mo-ink) !important;
        font-family: var(--mo-ui) !important;
    }

    /* Sidebar and topbar: flat, hairline-separated, no drop shadows. */
    .fi-sidebar,
    .fi-sidebar-nav,
    .fi-topbar > nav,
    .fi-topbar {
        background: var(--mo-panel) !important;
        border-color: var(--mo-line) !important;
        box-shadow: none !important;
    }

    .fi-sidebar-header {
        background: var(--mo-panel) !important;
        box-shadow: none !important;
        border-bottom: 1px solid var(--mo-line) !important;
    }

    /* Brand: the site's letterspaced wordmark, not a default logo slot. */
    .fi-logo {
        font-family: var(--mo-display) !important;
        font-size: 1.15rem !important;
        font-weight: 500 !important;
        letter-spacing: 0.42em !important;
        text-transform: uppercase !important;
        color: var(--mo-ink) !important;
    }

    .fi-sidebar-group-label,
    .fi-sidebar-item-label {
        font-family: var(--mo-ui) !important;
    }

    .fi-sidebar-group-label {
        text-transform: uppercase !important;
        letter-spacing: 0.18em !important;
        font-size: 0.66rem !important;
        color: var(--mo-ink-muted) !important;
    }

    .fi-sidebar-item-active .fi-sidebar-item-button {
        background: rgba(209, 85, 46, 0.16) !important;
    }

    /* Page furniture */
    .fi-page-header-heading,
    .fi-header-heading,
    .fi-modal-heading,
    .fi-section-header-heading,
    .fi-wi-stats-overview-stat-value {
        font-family: var(--mo-display) !important;
        font-weight: 500 !important;
        letter-spacing: 0.06em !important;
        text-transform: uppercase !important;
    }

    .fi-page-header-heading,
    .fi-header-heading {
        font-size: 1.9rem !important;
    }

    /* Cards, tables and panels sit one step above the ground. */
    .fi-section,
    .fi-ta-ctn,
    .fi-wi-stats-overview-stat,
    .fi-modal-window,
    .fi-dropdown-panel {
        background: var(--mo-panel) !important;
        border: 1px solid var(--mo-line) !important;
        border-radius: 6px !important;
        box-shadow: none !important;
    }

    .fi-ta-header-cell,
    .fi-ta-header-ctn {
        background: var(--mo-panel-raised) !important;
    }

    .fi-ta-header-cell-label {
        text-transform: uppercase !important;
        letter-spacing: 0.14em !important;
        font-size: 0.68rem !important;
        color: var(--mo-ink-muted) !important;
    }

    .fi-ta-row:hover {
        background: rgba(243, 243, 243, 0.03) !important;
    }

    /* Buttons: squarer and quieter than stock, in the site's register. */
    .fi-btn {
        font-family: var(--mo-ui) !important;
        text-transform: uppercase !important;
        letter-spacing: 0.1em !important;
        font-size: 0.74rem !important;
        border-radius: 4px !important;
        box-shadow: none !important;
    }

    .fi-btn.fi-color-primary {
        background: var(--mo-ember) !important;
        color: #0b0b0b !important;
    }

    .fi-btn.fi-color-primary:hover {
        background: #e2653a !important;
    }

    /* Fields — the ring carries the border in Filament, not border-color. */
    .fi-input-wrp {
        background: var(--mo-panel-raised) !important;
        border-radius: 4px !important;
        --tw-ring-color: var(--mo-line) !important;
    }

    .fi-input-wrp:focus-within {
        --tw-ring-color: var(--mo-ember) !important;
    }

    .fi-fo-field-wrp-label {
        text-transform: uppercase !important;
        letter-spacing: 0.12em !important;
        font-size: 0.68rem !important;
        color: var(--mo-ink-muted) !important;
    }

    .fi-badge {
        border-radius: 3px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.08em !important;
    }

    @media (prefers-reduced-motion: reduce) {
        * {
            animation-duration: 0.001ms !important;
            transition-duration: 0.001ms !important;
        }
    }
</style>
