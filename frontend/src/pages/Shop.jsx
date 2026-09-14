import { useEffect, useMemo, useRef, useState } from 'react'
import Toast from '../components/feedback/Toast'
import ProductCard from '../components/shop/ProductCard'
import ShopHeader from '../components/shop/ShopHeader'
import { useCart } from '../cart/useCart'
import { fetchShop } from '../shopApi'

export default function Shop() {
  const [data, setData] = useState(null)
  const addedTimer = useRef(null)
  const [error, setError] = useState(null)
  const [category, setCategory] = useState('all')
  const [added, setAdded] = useState(null)
  const { add } = useCart()

  useEffect(() => {
    let cancelled = false

    fetchShop()
      .then((payload) => !cancelled && setData(payload))
      .catch((problem) => !cancelled && setError(problem))

    return () => {
      cancelled = true
    }
  }, [])

  const products = useMemo(() => {
    const all = data?.products ?? []

    return category === 'all' ? all : all.filter((product) => product.category === category)
  }, [data, category])

  // One timer, replaced on each add and cleared on unmount, so a fast tapper
  // cannot leave a pile of them running.
  useEffect(() => () => window.clearTimeout(addedTimer.current), [])

  function addToCart(product) {
    add(product, 1)
    setAdded({ name: product.name, at: Date.now() })

    window.clearTimeout(addedTimer.current)
    addedTimer.current = window.setTimeout(() => setAdded(null), 2600)
  }

  return (
    <main className="shop">
      <ShopHeader eyebrow="Mophonik" title="The Shop" />

      {error ? (
        <p className="prose mt-8">{error.message}</p>
      ) : (
        <>
          <div className="filters" role="group" aria-label="Filter by category">
            <button
              type="button"
              className={`chip ${category === 'all' ? 'is-active' : ''}`}
              onClick={() => setCategory('all')}
              aria-pressed={category === 'all'}
            >
              All
            </button>

            {(data?.categories ?? []).map((item) => (
              <button
                key={item.slug}
                type="button"
                className={`chip ${category === item.slug ? 'is-active' : ''}`}
                onClick={() => setCategory(item.slug)}
                aria-pressed={category === item.slug}
              >
                {item.name}
              </button>
            ))}
          </div>

          {data === null ? (
            <p className="muted">Loading…</p>
          ) : products.length === 0 ? (
            <div className="empty">
              <p>Nothing in this category yet.</p>
            </div>
          ) : (
            <div className="product-grid">
              {products.map((product) => (
                <ProductCard key={product.id} product={product} onAdd={addToCart} />
              ))}
            </div>
          )}
        </>
      )}

      <Toast message={added ? `${added.name} added` : null} />
    </main>
  )
}
