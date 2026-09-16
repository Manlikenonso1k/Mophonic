<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SiteSetting;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ReceiptRenderer
{
    /**
     * @param  bool  $staff  Adds the internal fields: email, order status,
     *                       timestamps and notes.
     */
    public function view(Order $order, bool $staff = false): View
    {
        $settings = SiteSetting::current();

        return view('receipts.order', [
            'order' => $order->loadMissing('items'),
            'staff' => $staff,
            'money' => fn (int $kobo): string => Money::format($kobo),
            'business' => [
                'name' => $settings->business_name ?: config('app.name'),
                'phone' => $settings->business_phone,
                'email' => $settings->business_email,
                'address' => $settings->business_address,
            ],
        ]);
    }

    public function download(Order $order, bool $staff = false): Response
    {
        return Pdf::loadHTML($this->view($order, $staff)->render())
            ->setPaper('a4')
            ->download("receipt-{$order->reference}.pdf");
    }

    /**
     * A short-lived signed link. The reference is already unguessable, and the
     * signature means a leaked link cannot be replayed indefinitely.
     *
     * Deliberately relative: the shopper then fetches it from whatever origin
     * served the page, so the download works without CORS, and the signature
     * survives a host that does not match APP_URL.
     */
    public function signedUrl(Order $order): string
    {
        return URL::temporarySignedRoute(
            'shop.receipt',
            now()->addDays(30),
            ['reference' => $order->reference],
            absolute: false,
        );
    }
}
