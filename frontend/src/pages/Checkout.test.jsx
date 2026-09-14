import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { CartProvider } from '../cart/CartProvider'
import { STORAGE_KEY } from '../cart/cartContext'
import Checkout from './Checkout'

const placeOrder = vi.fn()

vi.mock('../shopApi', () => ({
  fetchShop: () => Promise.resolve({ deliveryFee: 0, products: [], categories: [] }),
  placeOrder: (...args) => placeOrder(...args),
}))

const LINES = [
  { productId: 1, slug: 'chin-chin', name: 'Chin Chin', priceKobo: 250000, unitLabel: '400g bag', stock: 12, quantity: 1 },
]

function renderCheckout() {
  window.localStorage.setItem(STORAGE_KEY, JSON.stringify(LINES))

  return render(
    <MemoryRouter initialEntries={['/checkout']}>
      <CartProvider>
        <Routes>
          <Route path="/checkout" element={<Checkout />} />
          <Route path="/order/:reference" element={<h1>Thank you</h1>} />
        </Routes>
      </CartProvider>
    </MemoryRouter>,
  )
}

async function fillIn(user) {
  await user.type(screen.getByLabelText(/full name/i), 'Ada Obi')
  await user.type(screen.getByLabelText(/^email/i), 'ada.obi@example.com')
  await user.type(screen.getByLabelText(/delivery address/i), '14 Bode Thomas, Lagos')
}

const placeOrderButton = () => screen.getByRole('button', { name: /place order/i })

describe('checkout', () => {
  beforeEach(() => {
    placeOrder.mockReset()
    window.localStorage.clear()
  })

  it('shows the reason when the request fails with no field errors', async () => {
    const user = userEvent.setup()
    // A dropped connection: a bare TypeError, no `errors` payload. This used
    // to set a notice that nothing rendered, so checkout failed in silence.
    placeOrder.mockRejectedValue(new TypeError('Failed to fetch'))

    renderCheckout()
    await fillIn(user)
    await user.click(placeOrderButton())

    expect(await screen.findByRole('alert')).toHaveTextContent(/failed to fetch/i)
  })

  it('falls back to a readable message when the failure carries none', async () => {
    const user = userEvent.setup()
    placeOrder.mockRejectedValue(new Error(''))

    renderCheckout()
    await fillIn(user)
    await user.click(placeOrderButton())

    expect(await screen.findByRole('alert')).toHaveTextContent(/could not reach the server/i)
  })

  it('places the order once even when the button is tapped twice', async () => {
    const user = userEvent.setup()
    let release
    placeOrder.mockImplementation(() => new Promise((resolve) => { release = resolve }))

    renderCheckout()
    await fillIn(user)

    const button = placeOrderButton()
    await user.click(button)
    await user.click(button)

    expect(placeOrder).toHaveBeenCalledTimes(1)

    release({ requires_payment: false, reference: 'SHOP-TEST-1' })
    await waitFor(() => expect(screen.getByText('Thank you')).toBeInTheDocument())
  })

  it('hands the shopper to Paystack when the server asks for payment', async () => {
    const user = userEvent.setup()
    placeOrder.mockResolvedValue({
      requires_payment: true,
      authorization_url: 'https://checkout.paystack.com/abc123',
      reference: 'SHOP-TEST-2',
    })

    const assign = vi.fn()
    const original = window.location
    delete window.location
    window.location = { ...original, set href(value) { assign(value) } }

    renderCheckout()
    await fillIn(user)
    await user.click(placeOrderButton())

    await waitFor(() => expect(assign).toHaveBeenCalledWith('https://checkout.paystack.com/abc123'))

    window.location = original
  })
})
