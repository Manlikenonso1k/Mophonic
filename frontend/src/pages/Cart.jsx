import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import QuantityStepper from '../components/shop/QuantityStepper'
import ShopHeader from '../components/shop/ShopHeader'
import { useCart } from '../cart/useCart'
import { formatKobo } from '../money'
import { fetchShop } from '../shopApi'

export default function Cart() {
  const { lines, subtotalKobo, setQuantity, remove } = useCart()
  const [deliveryKobo, setDeliveryKobo] = useState(0)

  useEffect(() => {
    let cancelled = false

    fetchShop()
      .then((payload) => !cancelled && setDeliveryKobo(payload.deliveryFee ?? 0))
      .catch(() => {})

    return () => {
      cancelled = true
    }
  }, [])

  if (lines.length === 0) {
    return (
      <main className="shop">
        <ShopHeader eyebrow="Mophonik" title="Your cart" />
        <div className="empty mt-8">
          <p className="mb-5">Your cart is empty.</p>
          <Link to="/shop" className="button">
            <span>Browse the shop</span>
          </Link>
        </div>
      </main>
    )
  }

  return (
    <main className="shop">
      <ShopHeader eyebrow="Mophonik" title="Your cart" backTo="/shop" backLabel="Shop" />

      <div className="grid gap-12 lg:grid-cols-[1fr_360px] mt-8">
        <section aria-label="Cart items">
          {lines.map((line) => (
            <div className="line-item" key={line.productId}>
              {line.image ? (
                <img className="thumb" src={line.image} alt="" width="76" height="95" />
              ) : (
                <div className="thumb" />
              )}

              <div>
                <Link to={`/shop/${line.slug}`} className="ttf duration-200 hover:opacity-70">
                  {line.name}
                </Link>
                {line.unitLabel ? <p className="muted mt-1">{line.unitLabel}</p> : null}
                <p className="price mt-1">{formatKobo(line.priceKobo)}</p>
              </div>

              <div className="line-actions flex items-center gap-4">
                <QuantityStepper
                  value={line.quantity}
                  max={line.stock ?? 99}
                  label={`Quantity for ${line.name}`}
                  onChange={(next) => setQuantity(line.productId, next)}
                />

                <span className="price w-[92px] text-right">
                  {formatKobo(line.priceKobo * line.quantity)}
                </span>

                <button
                  type="button"
                  className="button bare"
                  onClick={(event) => {
                    // Removing is destructive and sits next to the controls
                    // that lead to checkout: it must never ride a bubbled tap
                    // or a default action on the way out.
                    event.preventDefault()
                    event.stopPropagation()
                    remove(line.productId)
                  }}
                  aria-label={`Remove ${line.name} from cart`}
                >
                  <span>Remove</span>
                </button>
              </div>
            </div>
          ))}
        </section>

        <aside className="panel h-fit lg:sticky lg:top-[96px]">
          <h2 className="shop-eyebrow mb-4">Summary</h2>

          <div className="totals-row">
            <span>Subtotal</span>
            <span>{formatKobo(subtotalKobo)}</span>
          </div>
          <div className="totals-row">
            <span>Delivery</span>
            <span>{deliveryKobo === 0 ? 'Free' : formatKobo(deliveryKobo)}</span>
          </div>
          <div className="totals-row total">
            <span>Total</span>
            <span>{formatKobo(subtotalKobo + deliveryKobo)}</span>
          </div>

          <Link to="/checkout" className="button large w-full mt-6">
            <span>Checkout</span>
          </Link>

          <p className="muted mt-4 text-center">Totals are confirmed by the server at checkout.</p>
        </aside>
      </div>
    </main>
  )
}
