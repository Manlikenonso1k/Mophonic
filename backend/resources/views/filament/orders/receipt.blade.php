<x-filament-panels::page>
    {{-- The receipt is rendered by the same Blade the PDF uses, dropped into an
         iframe so its print styles cannot leak into the panel and vice versa.
         Printing from here gives the same sheet the customer gets. --}}
    <div class="mo-receipt-frame">
        <iframe
            title="Receipt {{ $this->getRecord()->reference }}"
            srcdoc="{{ $receipt }}"
            onload="this.style.height = (this.contentWindow.document.body.scrollHeight + 32) + 'px'"
        ></iframe>
    </div>

    <style>
        .mo-receipt-frame {
            background: #fff;
            border-radius: 0.5rem;
            padding: 0.5rem;
            max-width: 460px;
        }

        .mo-receipt-frame iframe {
            width: 100%;
            min-height: 640px;
            border: 0;
            display: block;
        }

        /* Print the sheet alone: no sidebar, no topbar, no page chrome. */
        @media print {
            .fi-sidebar, .fi-topbar, .fi-header, .fi-btn { display: none !important; }
            .fi-main, .fi-page, .fi-main-ctn { padding: 0 !important; margin: 0 !important; }
            .mo-receipt-frame { max-width: none; padding: 0; }
            .mo-receipt-frame iframe { min-height: 100vh; }
        }
    </style>
</x-filament-panels::page>
