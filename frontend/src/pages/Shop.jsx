import { useEffect, useMemo, useState } from 'react'
import ProductCard from '../components/shop/ProductCard'
import ShopHeader from '../components/shop/ShopHeader'
import { useCart } from '../cart/useCart'
import { fetchShop } from '../shopApi'

export default function Shop() {
  const [data, setData] = useState(null)
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

  function addToCart(product) {
    add(product, 1)
    setAdded(product.name)
    window.setTimeout(() => setAdded(null), 2600)
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

      <p className="sr-only" role="status" aria-live="polite">
        {added ? `${added} added to cart` : ''}
      </p>

      {added ? (
        <div className="fixed bottom-[104px] left-1/2 -translate-x-1/2 z-[12] button no-blur">
          <span>{added} added</span>
        </div>
      ) : null}
    </main>
  )
}
