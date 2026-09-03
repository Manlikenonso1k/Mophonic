const naira = new Intl.NumberFormat('en-NG', {
  style: 'currency',
  currency: 'NGN',
  minimumFractionDigits: 0,
  maximumFractionDigits: 0,
})

/** Prices travel as kobo (minor units) and are only ever formatted here. */
export function formatKobo(kobo) {
  return naira.format(Math.round((kobo ?? 0) / 100))
}
