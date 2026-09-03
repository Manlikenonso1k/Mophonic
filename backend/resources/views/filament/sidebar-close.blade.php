{{--
    Injected at panels::sidebar.start. Filament ships a click-to-close overlay
    but no explicit control, which is not discoverable on a phone. Escape is
    bound here too — Filament's sidebar store has no key handling of its own.
    Both are limited to the mobile breakpoint so desktop behaviour is untouched.
--}}
<div
    x-data="{}"
    x-on:keydown.escape.window="if (window.innerWidth < 1024) { $store.sidebar.close() }"
    class="mo-sidebar-close-ctn"
>
    <button
        type="button"
        x-on:click="$store.sidebar.close()"
        class="mo-sidebar-close"
        aria-label="{{ __('Close menu') }}"
        aria-controls="fi-main-sidebar"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="M5 5l10 10M15 5L5 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </svg>
    </button>
</div>
