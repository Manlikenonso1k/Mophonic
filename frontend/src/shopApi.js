const BASE = import.meta.env.VITE_API_BASE ?? ''

async function request(path, options = {}) {
  const response = await fetch(`${BASE}${path}`, {
    ...options,
    headers: {
      Accept: 'application/json',
      ...(options.body ? { 'Content-Type': 'application/json' } : {}),
      ...options.headers,
    },
  })

  const body = await response.json().catch(() => ({}))

  if (!response.ok) {
    const error = new Error(body.message ?? `Request failed (${response.status})`)
    error.status = response.status
    error.errors = body.errors ?? {}

    throw error
  }

  return body
}

export function fetchShop() {
  return request('/api/shop')
}

export function fetchProduct(slug) {
  return request(`/api/shop/products/${encodeURIComponent(slug)}`)
}

export function fetchOrder(reference) {
  return request(`/api/orders/${encodeURIComponent(reference)}`)
}

/** The server prices the cart; this only sends product ids and quantities. */
export function placeOrder({ customer, items }) {
  return request('/api/orders', {
    method: 'POST',
    body: JSON.stringify({
      ...customer,
      items: items.map((item) => ({ product_id: item.productId, quantity: item.quantity })),
    }),
  })
}
