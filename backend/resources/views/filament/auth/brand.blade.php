{{-- Injected at panels::simple-page.start, scoped to the Login page. --}}
<style>
    .fi-simple-main {
        background: var(--mo-panel) !important;
        border: 1px solid var(--mo-line) !important;
        border-radius: 8px !important;
        box-shadow: none !important;
    }

    .fi-simple-layout {
        position: relative;
    }

    /* A slow ember bloom behind the card, in the register of the site's covers. */
    .fi-simple-layout::before {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: radial-gradient(60% 50% at 50% 38%, rgba(209, 85, 46, 0.20), transparent 70%);
    }

    .mo-brand {
        text-align: center;
        margin-bottom: 2rem;
    }

    .mo-brand-mark {
        font-family: var(--mo-display);
        font-size: 1.5rem;
        font-weight: 500;
        letter-spacing: 0.46em;
        text-indent: 0.46em;
        text-transform: uppercase;
        color: var(--mo-ink);
    }

    .mo-brand-eyebrow {
        font-family: var(--mo-ui);
        font-size: 0.66rem;
        letter-spacing: 0.24em;
        text-transform: uppercase;
        color: var(--mo-ink-muted);
        margin-bottom: 0.75rem;
    }

    .mo-brand-rule {
        width: 34px;
        height: 1px;
        margin: 1.25rem auto 0;
        background: var(--mo-ember);
    }

    /* Filament's own brand block would repeat the wordmark below ours. */
    .fi-simple-layout-header {
        display: none !important;
    }

    /* Field labels, in the same register as the rest of the panel. */
    .fi-simple-main label:not(.fi-sr-only) {
        text-transform: uppercase !important;
        letter-spacing: 0.12em !important;
        font-size: 0.68rem !important;
        font-weight: 500 !important;
        color: var(--mo-ink-muted) !important;
    }

    /* The password reveal button ships with its own background. */
    .fi-input-wrp-suffix,
    .fi-input-wrp-actions,
    .fi-input-wrp-suffix .fi-icon-btn {
        background: transparent !important;
        box-shadow: none !important;
    }

    /* Filament's checkbox is border-none over a dark ring — invisible here. */
    .fi-simple-main input[type='checkbox'] {
        --tw-ring-color: rgba(243, 243, 243, 0.32) !important;
    }
</style>

<div class="mo-brand">
    <div class="mo-brand-eyebrow">Staff access</div>
    <div class="mo-brand-mark">Mophonik</div>
    <div class="mo-brand-rule"></div>
</div>
