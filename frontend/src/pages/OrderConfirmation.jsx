import { useEffect, useState } from 'react'
import { Link, useParams, useSearchParams } from 'react-router-dom'
import Confetti from '../components/feedback/Confetti'
import ShopHeader from '../components/shop/ShopHeader'
import { formatKobo } from '../money'
import { fetchOrder } from '../shopApi'

const PAYMENT_COPY = {
  paid: 'Payment received.',
  pending: 'We have your order — payment is still pending.',
  failed: 'Payment did not go through. Get in touch and we will sort it out.',
}

function placedOn(iso) {
  if (!iso) {
    return null
  }

  return new Date(iso).toLocaleString(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
  })
}


/**
 * True only the first time a paid order is seen in this tab. sessionStorage is
 * what keeps a refresh, or a back-navigation, from firing the celebration again.
 */
function firstSightOfPayment(order) {
  if (order?.paymentStatus !== 'paid') {
    return false
  }

  const key = `mophonik.celebrated.${order.reference}`

  try {
    if (window.sessionStorage.getItem(key)) {
      return false
    }

    window.sessionStorage.setItem(key, '1')
  } catch {
    // Private browsing: celebrate rather than fail.
  }

  return true
}

/**
 * Addressed by reference, and loaded from the server every time — the cart is
 * cleared before the shopper gets here, and they may arrive days later from a
 * link. Payment state comes from the verified order, never from the client.
 */
export default function OrderConfirmation() {
  const { reference } = useParams()
  const [params] = useSearchParams()
  const [order, setOrder] = useState(null)
  const [error, setError] = useState(null)
  const [celebrate, setCelebrate] = useState(false)

  useEffect(() => {
    if (!reference) {
      return undefined
    }

    let cancelled = false

    fetchOrder(reference)
      .then((payload) => {
        if (cancelled) {
          return
        }

        setOrder(payload.order)
        setCelebrate(firstSightOfPayment(payload.order))
      })
      .catch((problem) => !cancelled && setError(problem))

    return () => {
      cancelled = true
    }
  }, [reference])

  // The confetti is a one-shot: it clears itself, and the timer is cancelled
  // if the page goes away first.
  useEffect(() => {
    if (!celebrate) {
      return undefined
    }

    const timer = window.setTimeout(() => setCelebrate(false), 2800)

    return () => window.clearTimeout(timer)
  }, [celebrate])

  const paymentFlag = params.get('payment')

  return (
    <main className="shop">
      {celebrate ? <Confetti /> : null}

      <ShopHeader eyebrow="Mophonik" title="Order received" />

      <div className="panel mt-8 max-w-[560px]">
        {!reference || error ? (
          <p className="prose">
            We could not find that order. If you have just paid, check your email for the receipt.
          </p>
        ) : !order ? (
          <p className="muted">Loading…</p>
        ) : (
          <>
            <p className="shop-eyebrow">Reference</p>
            <p className="mb-6">{order.reference}</p>

            {order.paymentStatus === 'paid' ? (
              <p className="mo-success mb-4" role="status">
                Payment confirmed — thank you.
              </p>
            ) : null}

            <p className="prose mb-6">
              {paymentFlag === 'unverified'
                ? 'We could not confirm the payment yet. If it left your account, it will land shortly.'
                : (PAYMENT_COPY[order.paymentStatus] ?? 'Your order is with our team.')}
            </p>

            {order.items.map((item, index) => (
              <div className="totals-row" key={`${item.name}-${index}`}>
                <span>
                  {item.quantity} × {item.name}
                </span>
                <span>{formatKobo(item.lineTotalKobo)}</span>
              </div>
            ))}

            <div className="totals-row" style={{ opacity: 0.6 }}>
              <span>Delivery</span>
              <span>
                {order.deliveryFeeKobo === 0 ? 'Free' : formatKobo(order.deliveryFeeKobo)}
              </span>
            </div>

            <div className="totals-row total">
              <span>Total</span>
              <span>{formatKobo(order.totalKobo)}</span>
            </div>

            <div className="mt-8 grid gap-1">
              <p className="shop-eyebrow">Delivery</p>
              <p>{order.customerName}</p>
              {order.customerPhone ? <p className="muted">{order.customerPhone}</p> : null}
              {order.deliveryAddress ? (
                <p className="muted whitespace-pre-line">{order.deliveryAddress}</p>
              ) : null}
              {placedOn(order.placedAt) ? (
                <p className="muted mt-2">Placed {placedOn(order.placedAt)}</p>
              ) : null}
            </div>
          </>
        )}

        <div className="mt-8 flex flex-wrap gap-3">
          {order?.receiptUrl ? (
            <a className="button large" href={order.receiptUrl} download>
              <span>Download receipt</span>
            </a>
          ) : null}

          <Link to="/shop" className="button large">
            <span>Back to shop</span>
          </Link>
        </div>
      </div>
    </main>
  )
}
