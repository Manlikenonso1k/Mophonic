import { useEffect, useState } from 'react'
import { Link, Route, Routes, useLocation } from 'react-router-dom'
import { fetchSite } from './api'
import { CartProvider } from './cart/CartProvider'
import { useCart } from './cart/useCart'
import Menu from './components/Menu'
import Navigation from './components/Navigation'
import Wordmark from './components/Wordmark'
import useBodyClass from './hooks/useBodyClass'
import Cart from './pages/Cart'
import Checkout from './pages/Checkout'
import Mophonik from './pages/Mophonik'
import ProductDetail from './pages/ProductDetail'
import Shop from './pages/Shop'
import ThankYou from './pages/ThankYou'
import WorkDetail from './pages/WorkDetail'

const FALLBACK_SETTINGS = {
  eyebrow: 'EXPLORE',
  heading: 'MOPHONIK',
  shopUrl: '/shop',
  termsUrl: '/terms',
  newsletterHeading: 'Enter email for updates',
  menuItems: [],
}

/** Top-right pill: the shop entrance, or the cart once you are inside it. */
function CornerAction({ shopUrl }) {
  const { pathname } = useLocation()
  const { count } = useCart()
  const inShop = pathname.startsWith('/shop') || pathname === '/cart' || pathname === '/checkout'
  const classes = 'button fixed top-[24px] right-[13px] sm:top-[30px] sm:right-[30px] z-[11]'

  if (!inShop) {
    return /^https?:\/\//i.test(shopUrl) ? (
      <a href={shopUrl} target="_blank" rel="noreferrer" className={classes}>
        <span>Shop</span>
      </a>
    ) : (
      <Link to={shopUrl} className={classes}>
        <span>Shop</span>
      </Link>
    )
  }

  return (
    <Link to="/cart" className={`${classes} cart-pill`}>
      <span>Cart{count > 0 ? ` (${count})` : ''}</span>
    </Link>
  )
}

function Chrome({ settings }) {
  const { pathname } = useLocation()
  const [state, setState] = useState({ path: pathname, menuOpen: false, emailOpen: false })

  // A route change closes the menu in the same render, including on browser back.
  if (state.path !== pathname) {
    setState({ path: pathname, menuOpen: false, emailOpen: false })
  }

  const { menuOpen, emailOpen } = state

  useBodyClass('js-menu-open', menuOpen)
  useBodyClass('js-modal-open', menuOpen)
  useBodyClass('js-email-open', emailOpen)

  function toggleMenu() {
    setState((current) => ({ ...current, menuOpen: !current.menuOpen, emailOpen: false }))
  }

  function closeMenu() {
    setState((current) => ({ ...current, menuOpen: false, emailOpen: false }))
  }

  function openEmail() {
    setState((current) => ({ ...current, emailOpen: true }))
  }

  return (
    <>
      <CornerAction shopUrl={settings.shopUrl} />

      <Wordmark className="home-logo ttf duration-300 fixed left-1/2 -translate-x-1/2 top-[16px] sm:top-[18px] z-[20]" />

      <Navigation active={menuOpen} onToggle={toggleMenu} />

      <Menu
        settings={settings}
        emailOpen={emailOpen}
        onOpenEmail={openEmail}
        onNavigate={closeMenu}
      />
    </>
  )
}

export default function App() {
  const [site, setSite] = useState(null)
  const [error, setError] = useState(null)

  useEffect(() => {
    let cancelled = false

    fetchSite()
      .then((data) => !cancelled && setSite(data))
      .catch((problem) => !cancelled && setError(problem))

    return () => {
      cancelled = true
    }
  }, [])

  const settings = site?.settings ?? FALLBACK_SETTINGS
  const works = site?.works ?? []

  return (
    <CartProvider>
      <Chrome settings={settings} />

      {error ? (
        <main className="flex h-[100dvh] flex-col items-center justify-center text-center px-[20px]">
          <p className="mb-[20px]">The site could not be loaded.</p>
          <p className="opacity-50">{error.message}</p>
        </main>
      ) : (
        <Routes>
          <Route path="/" element={<Mophonik settings={settings} works={works} />} />
          <Route path="/mophonik/:slug" element={<WorkDetail works={works} loading={!site} />} />
          <Route path="/shop" element={<Shop />} />
          <Route path="/shop/thank-you" element={<ThankYou />} />
          <Route path="/shop/:slug" element={<ProductDetail />} />
          <Route path="/cart" element={<Cart />} />
          <Route path="/checkout" element={<Checkout />} />
          <Route path="*" element={<Mophonik settings={settings} works={works} />} />
        </Routes>
      )}
    </CartProvider>
  )
}
