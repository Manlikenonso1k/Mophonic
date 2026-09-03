import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import QuantityStepper from '../components/shop/QuantityStepper'
import { useCart } from '../cart/useCart'
import { formatKobo } from '../money'
import { fetchProduct } from '../shopApi'

export default function ProductDetail() {
  const { slug } = useParams()
  // Keyed by slug, so navigating between products shows the loader rather
  // than the previous product while the next one is in flight.
  const [loaded, setLoaded] = useState({ slug: null, product: null, error: null })
  const [quantity, setQuantity] = useState(1)
  const [added, setAdded] = useState(false)
  const { add } = useCart()

  useEffect(() => {
    let cancelled = false

    fetchProduct(slug)
      .then((payload) => !cancelled && setLoaded({ slug, product: payload.product, error: null }))
      .catch((problem) => !cancelled && setLoaded({ slug, product: null, error: problem }))

    return () => {
      cancelled = true
    }
  }, [slug])

  const product = loaded.slug === slug ? loaded.product : null
  const error = loaded.slug === slug ? loaded.error : null

  if (error) {
    return (
      <main className="shop">
        <div className="empty">
          <p className="mb-5">That product is no longer available.</p>
          <Link to="/shop" className="button">
            <span>Back to shop</span>
          </Link>
        </div>
      </main>
    )
  }

  if (!product) {
    return (
      <main className="shop">
        <p className="muted">Loading…</p>
      </main>
    )
  }

  return (
    <main className="shop">
      <Link to="/shop" className="button bare mb-8 inline-flex">
        <span>← Shop</span>
      </Link>

      <div className="grid gap-10 md:grid-cols-2 md:gap-16 items-start">
        <div className="product-card !border-white/10 max-w-[520px] w-full">
          <div className="frame">
            {product.image ? (
              <img src={product.image} alt={product.name} width="900" height="1125" />
            ) : null}
          </div>
        </div>

        <div className="max-w-[520px]">
          <p className="shop-eyebrow">{product.categoryName}</p>
          <h1 className="shop-title">{product.name}</h1>

          <p className="price mt-5 text-[20px]">{formatKobo(product.priceKobo)}</p>
          {product.unitLabel ? <p className="muted mt-1">{product.unitLabel}</p> : null}

          {product.description ? <p className="prose mt-6">{product.description}</p> : null}

          <p className="muted mt-6">
            {product.inStock ? `${product.stock} in stock` : 'Currently sold out'}
          </p>

          <div className="mt-7 flex flex-wrap items-center gap-4">
            <QuantityStepper
              value={quantity}
              max={Math.max(product.stock, 1)}
              onChange={(next) => setQuantity(Math.max(1, Math.min(next, product.stock || 1)))}
            />

            <button
              type="button"
              className="button large"
              disabled={!product.inStock}
              onClick={() => {
                add(product, quantity)
                setAdded(true)
                window.setTimeout(() => setAdded(false), 2600)
              }}
            >
              <span>{product.inStock ? 'Add to cart' : 'Sold out'}</span>
            </button>

            <Link to="/cart" className="button large bare">
              <span>View cart</span>
            </Link>
          </div>

          <p className="muted mt-4" role="status" aria-live="polite">
            {added ? 'Added to your cart.' : ''}
          </p>
        </div>
      </div>
    </main>
  )
}
