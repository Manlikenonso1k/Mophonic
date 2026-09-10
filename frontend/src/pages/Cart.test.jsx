import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { CartProvider } from '../cart/CartProvider'
import { STORAGE_KEY } from '../cart/cartContext'
import Cart from './Cart'

vi.mock('../shopApi', () => ({
  fetchShop: () => Promise.resolve({ deliveryFee: 0, products: [], categories: [] }),
}))

const LINES = [
  { productId: 1, slug: 'chin-chin', name: 'Chin Chin', priceKobo: 250000, unitLabel: '400g bag', stock: 12, quantity: 1 },
  { productId: 2, slug: 'suya-spice', name: 'Suya Spice', priceKobo: 320000, unitLabel: '200g jar', stock: 19, quantity: 2 },
]

function renderCart() {
  window.localStorage.setItem(STORAGE_KEY, JSON.stringify(LINES))

  return render(
    <MemoryRouter initialEntries={['/cart']}>
      <CartProvider>
        <Routes>
          <Route path="/cart" element={<Cart />} />
          <Route path="/checkout" element={<h1>Checkout page</h1>} />
        </Routes>
      </CartProvider>
    </MemoryRouter>,
  )
}

const storedLines = () => JSON.parse(window.localStorage.getItem(STORAGE_KEY) ?? '[]')

describe('cart', () => {
  beforeEach(() => {
    window.localStorage.clear()
  })

  // The regression this file exists for. A tap on Checkout used to land on a
  // Remove button's :after halo, which — unpositioned — covered the whole page.
  it('tapping checkout navigates and removes nothing', async () => {
    const user = userEvent.setup()
    renderCart()

    expect(screen.getAllByRole('button', { name: /remove/i })).toHaveLength(2)

    await user.click(screen.getByRole('link', { name: /checkout/i }))

    expect(await screen.findByText('Checkout page')).toBeInTheDocument()
    await waitFor(() => expect(storedLines()).toHaveLength(2))
  })

  it('tapping remove takes out exactly that line', async () => {
    const user = userEvent.setup()
    renderCart()

    await user.click(screen.getByRole('button', { name: 'Remove Chin Chin from cart' }))

    await waitFor(() => expect(storedLines()).toHaveLength(1))
    expect(storedLines()[0].productId).toBe(2)
    expect(screen.queryByText('Chin Chin')).not.toBeInTheDocument()
  })

  it('remove stops the event so no ancestor handler can also act on the tap', async () => {
    const user = userEvent.setup()
    const onAncestorClick = vi.fn()

    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(LINES))

    render(
      <MemoryRouter initialEntries={['/cart']}>
        {/* eslint-disable-next-line jsx-a11y/no-static-element-interactions */}
        <div onClick={onAncestorClick}>
          <CartProvider>
            <Cart />
          </CartProvider>
        </div>
      </MemoryRouter>,
    )

    await user.click(screen.getByRole('button', { name: 'Remove Chin Chin from cart' }))

    await waitFor(() => expect(storedLines()).toHaveLength(1))
    expect(onAncestorClick).not.toHaveBeenCalled()
  })

  it('quantity controls do not bubble either', async () => {
    const user = userEvent.setup()
    const onAncestorClick = vi.fn()

    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(LINES))

    render(
      <MemoryRouter initialEntries={['/cart']}>
        {/* eslint-disable-next-line jsx-a11y/no-static-element-interactions */}
        <div onClick={onAncestorClick}>
          <CartProvider>
            <Cart />
          </CartProvider>
        </div>
      </MemoryRouter>,
    )

    await user.click(screen.getAllByRole('button', { name: 'Increase quantity' })[0])

    await waitFor(() => expect(storedLines()[0].quantity).toBe(2))
    expect(onAncestorClick).not.toHaveBeenCalled()
  })

  // jsdom has no layout, so the hit-area half of the bug cannot be reproduced
  // here. This guards the stylesheet invariant that caused it instead: the
  // touch halo must resolve against the button, never a page-sized ancestor.
  it('keeps .button positioned so its touch halo cannot escape', () => {
    const css = readFileSync(resolve(process.cwd(), 'src/index.css'), 'utf8')
    const rule = css.slice(css.indexOf('\n.button {'), css.indexOf('.button span'))

    expect(rule).toMatch(/position:\s*relative/)
  })
})
