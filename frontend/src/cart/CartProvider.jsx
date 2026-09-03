import { useCallback, useEffect, useMemo, useState } from 'react'
import { CartContext, STORAGE_KEY } from './cartContext'

function read() {
  try {
    const stored = JSON.parse(window.localStorage.getItem(STORAGE_KEY) ?? '[]')

    return Array.isArray(stored) ? stored : []
  } catch {
    return []
  }
}

/**
 * The cart lives in the browser. What it holds is a display snapshot — the
 * server re-prices every line from its own table at checkout.
 */
export function CartProvider({ children }) {
  const [lines, setLines] = useState(read)

  useEffect(() => {
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(lines))
    } catch {
      // A private window with storage disabled still gets a working cart.
    }
  }, [lines])

  const add = useCallback((product, quantity = 1) => {
    setLines((current) => {
      const existing = current.find((line) => line.productId === product.id)
      const ceiling = product.stock ?? 99

      if (existing) {
        return current.map((line) =>
          line.productId === product.id
            ? { ...line, quantity: Math.min(line.quantity + quantity, ceiling) }
            : line,
        )
      }

      return [
        ...current,
        {
          productId: product.id,
          slug: product.slug,
          name: product.name,
          priceKobo: product.priceKobo,
          image: product.image,
          unitLabel: product.unitLabel,
          stock: product.stock,
          quantity: Math.min(quantity, ceiling),
        },
      ]
    })
  }, [])

  const setQuantity = useCallback((productId, quantity) => {
    setLines((current) =>
      current.flatMap((line) => {
        if (line.productId !== productId) {
          return [line]
        }

        const next = Math.min(Math.max(quantity, 0), line.stock ?? 99)

        return next === 0 ? [] : [{ ...line, quantity: next }]
      }),
    )
  }, [])

  const remove = useCallback((productId) => {
    setLines((current) => current.filter((line) => line.productId !== productId))
  }, [])

  const clear = useCallback(() => setLines([]), [])

  const value = useMemo(() => {
    const count = lines.reduce((total, line) => total + line.quantity, 0)
    const subtotalKobo = lines.reduce((total, line) => total + line.priceKobo * line.quantity, 0)

    return { lines, count, subtotalKobo, add, setQuantity, remove, clear }
  }, [lines, add, setQuantity, remove, clear])

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>
}
