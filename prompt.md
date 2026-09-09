Add Telegram notifications to this Laravel app.

Setup:
- Config: add TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID, TELEGRAM_ENABLED to .env
  and a config/telegram.php file. Never read env() outside config files.
- Create App\Services\TelegramNotifier with a send(string $message) method
  that POSTs to https://api.telegram.org/bot{token}/sendMessage using
  Laravel's Http client, with parse_mode 'HTML' and disable_web_page_preview true.
- Wrap the call in try/catch, log failures with Log::error, and never let a
  Telegram failure break the request that triggered it.
- Add Http::timeout(10) and ->retry(2, 200).

Delivery:
- Dispatch it from a queued job (ShouldQueue) so checkout isn't blocked by
  the API call. Use the existing queue connection.

Notifications to send:
1. New order placed — order reference, customer name, phone, delivery address,
   line items with quantities, subtotal, delivery fee, total.
2. Payment successful — order reference, amount, gateway reference, channel.
3. Payment failed — order reference, amount, and the gateway's failure reason.

Formatting:
- Use HTML tags (<b>, <code>) not Markdown, to avoid escaping issues with
  customer names. Escape all user-supplied values with htmlspecialchars
  before interpolating.
- Prefix each message type with a distinct emoji so they're scannable in
  the group.

Hook the payment events into the existing webhook/callback handler rather
than the frontend redirect, so notifications fire even if the customer
closes the browser. Show me where you wired it in.