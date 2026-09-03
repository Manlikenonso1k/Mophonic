import { useEffect, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import ShopHeader from '../components/shop/ShopHeader'
import { formatKobo } from '../money'
import { fetchOrder } from '../shopApi'

const PAYMENT_COPY = {
  paid: 'Payment received.',
  pending: 'We have your order — payment is still pending.',
  failed: 'Payment did not go through. Get in touch and we will sort it out.',
}

export default function ThankYou() {
  const [params] = useSearchParams()
  const reference = params.get('reference')
  const [order, setOrder] = useState(null)
  const [error, setError] = useState(null)

  useEffect(() => {
    if (!reference) {
      return undefined
    }

    let cancelled = false

    fetchOrder(reference)
      .then((payload) => !cancelled && setOrder(payload.order))
      .catch((problem) => !cancelled && setError(problem))

    return () => {
      cancelled = true
    }
  }, [reference])

  return (
    <main className="shop">
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

            <p className="prose mb-6">
              {PAYMENT_COPY[order.paymentStatus] ?? 'Your order is with our team.'}
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
          </>
        )}

        <Link to="/shop" className="button large mt-8 inline-flex">
          <span>Back to shop</span>
        </Link>
      </div>
    </main>
  )
}
