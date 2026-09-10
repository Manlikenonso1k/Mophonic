import { useEffect, useRef, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import Field from '../components/shop/Field'
import ShopHeader from '../components/shop/ShopHeader'
import { useCart } from '../cart/useCart'
import { formatKobo } from '../money'
import { fetchShop, placeOrder } from '../shopApi'

const EMPTY = {
  customer_name: '',
  customer_email: '',
  customer_phone: '',
  delivery_address: '',
  notes: '',
}

const LABELS = {
  customer_name: 'Full name',
  customer_email: 'Email',
  customer_phone: 'Phone',
  delivery_address: 'Delivery address',
  items: 'Your cart',
}

export default function Checkout() {
  const { lines, subtotalKobo, clear } = useCart()
  const [form, setForm] = useState(EMPTY)
  const [errors, setErrors] = useState({})
  const [status, setStatus] = useState('idle')
  const [notice, setNotice] = useState(null)
  const [deliveryKobo, setDeliveryKobo] = useState(0)
  const summaryRef = useRef(null)
  const inFlight = useRef(false)
  const navigate = useNavigate()

  useEffect(() => {
    let cancelled = false

    fetchShop()
      .then((payload) => !cancelled && setDeliveryKobo(payload.deliveryFee ?? 0))
      .catch(() => {})

    return () => {
      cancelled = true
    }
  }, [])

  function update(field, value) {
    setForm((current) => ({ ...current, [field]: value }))
  }

  function validate() {
    const found = {}

    if (!form.customer_name.trim()) {
      found.customer_name = 'Enter your name.'
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.customer_email)) {
      found.customer_email = 'Enter a valid email address.'
    }

    if (!form.delivery_address.trim()) {
      found.delivery_address = 'Enter where the order should go.'
    }

    return found
  }

  async function onSubmit(event) {
    event.preventDefault()
    event.stopPropagation()

    // `disabled` only takes effect after a render. A second submit dispatched
    // in the same tick — a double tap, or a stray Enter — would otherwise
    // place the order twice.
    if (inFlight.current) {
      return
    }

    const found = validate()
    setErrors(found)

    if (Object.keys(found).length > 0) {
      window.requestAnimationFrame(() => summaryRef.current?.focus())

      return
    }

    inFlight.current = true
    setStatus('sending')
    setNotice(null)

    try {
      const result = await placeOrder({ customer: form, items: lines })

      // Paystack takes over from here when it is configured.
      if (result.requires_payment && result.authorization_url) {
        clear()
        window.location.href = result.authorization_url

        return
      }

      clear()
      navigate(`/shop/thank-you?reference=${encodeURIComponent(result.reference)}`)
    } catch (problem) {
      const serverErrors = problem.errors ?? {}
      const flattened = Object.fromEntries(
        Object.entries(serverErrors).map(([field, messages]) => [field, messages[0]]),
      )

      setErrors(flattened)
      setNotice(
        problem.message ||
          'We could not reach the server. Check your connection and try again.',
      )
      setStatus('idle')
      window.requestAnimationFrame(() => summaryRef.current?.focus())
    } finally {
      inFlight.current = false
    }
  }

  if (lines.length === 0 && status !== 'sending') {
    return (
      <main className="shop">
        <ShopHeader eyebrow="Mophonik" title="Checkout" />
        <div className="empty mt-8">
          <p className="mb-5">There is nothing to check out.</p>
          <Link to="/shop" className="button">
            <span>Browse the shop</span>
          </Link>
        </div>
      </main>
    )
  }

  const errorList = Object.entries(errors)
  const showSummary = errorList.length > 0 || Boolean(notice)

  return (
    <main className="shop">
      <ShopHeader eyebrow="Mophonik" title="Checkout" backTo="/cart" backLabel="Cart" />

      <form onSubmit={onSubmit} noValidate className="grid gap-12 lg:grid-cols-[1fr_360px] mt-8">
        <section>
          {showSummary ? (
            <div className="error-summary" role="alert" tabIndex={-1} ref={summaryRef}>
              <p className="mb-2">{notice ?? 'Please check the fields below.'}</p>
              <ul>
                {errorList.map(([field, message]) => (
                  <li key={field}>
                    <a href={`#${field}`}>{LABELS[field] ?? field}</a> — {message}
                  </li>
                ))}
              </ul>
            </div>
          ) : null}

          <Field
            id="customer_name"
            label="Full name"
            required
            value={form.customer_name}
            error={errors.customer_name}
            autoComplete="name"
            onChange={(event) => update('customer_name', event.target.value)}
            onBlur={() => setErrors((current) => ({ ...current, ...validate() }))}
          />

          <Field
            id="customer_email"
            label="Email"
            type="email"
            required
            value={form.customer_email}
            error={errors.customer_email}
            autoComplete="email"
            inputMode="email"
            onChange={(event) => update('customer_email', event.target.value)}
          />

          <Field
            id="customer_phone"
            label="Phone"
            type="tel"
            value={form.customer_phone}
            error={errors.customer_phone}
            autoComplete="tel"
            inputMode="tel"
            onChange={(event) => update('customer_phone', event.target.value)}
          />

          <Field
            id="delivery_address"
            label="Delivery address"
            as="textarea"
            required
            value={form.delivery_address}
            error={errors.delivery_address}
            autoComplete="street-address"
            onChange={(event) => update('delivery_address', event.target.value)}
          />

          <Field
            id="notes"
            label="Notes (optional)"
            as="textarea"
            value={form.notes}
            error={errors.notes}
            onChange={(event) => update('notes', event.target.value)}
          />
        </section>

        <aside className="panel h-fit lg:sticky lg:top-[96px]">
          <h2 className="shop-eyebrow mb-4">Order</h2>

          {lines.map((line) => (
            <div className="totals-row" key={line.productId}>
              <span>
                {line.quantity} × {line.name}
              </span>
              <span>{formatKobo(line.priceKobo * line.quantity)}</span>
            </div>
          ))}

          <div className="totals-row" style={{ opacity: 0.6 }}>
            <span>Delivery</span>
            <span>{deliveryKobo === 0 ? 'Free' : formatKobo(deliveryKobo)}</span>
          </div>

          <div className="totals-row total">
            <span>Total</span>
            <span>{formatKobo(subtotalKobo + deliveryKobo)}</span>
          </div>

          <button type="submit" className="button large w-full mt-6" disabled={status === 'sending'}>
            <span>{status === 'sending' ? 'Placing order…' : 'Place order'}</span>
          </button>

          <p className="muted mt-4 text-center">
            Payment is taken by Paystack. Prices are re-checked on the server.
          </p>
        </aside>
      </form>
    </main>
  )
}
