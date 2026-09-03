const BASE = import.meta.env.VITE_API_BASE ?? ''

export async function fetchSite() {
  const response = await fetch(`${BASE}/api/site`, {
    headers: { Accept: 'application/json' },
  })

  if (!response.ok) {
    throw new Error(`Could not load the site (${response.status})`)
  }

  return response.json()
}

export async function subscribe({ email, terms }) {
  const response = await fetch(`${BASE}/api/subscribe`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify({ email, terms }),
  })

  if (!response.ok) {
    const body = await response.json().catch(() => ({}))
    throw new Error(body.message ?? 'Something went wrong')
  }

  return response.json()
}
