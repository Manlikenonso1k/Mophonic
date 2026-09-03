import { useEffect, useState } from 'react'
import { Route, Routes } from 'react-router-dom'
import { fetchSite } from './api'
import Menu from './components/Menu'
import Navigation from './components/Navigation'
import Wordmark from './components/Wordmark'
import Mophonik from './pages/Mophonik'
import WorkDetail from './pages/WorkDetail'
import useBodyClass from './hooks/useBodyClass'

const FALLBACK_SETTINGS = {
  eyebrow: 'EXPLORE',
  heading: 'MOPHONIK',
  shopUrl: 'https://shop.travisscott.com/',
  termsUrl: 'https://shop.travisscott.com/pages/terms',
  newsletterHeading: 'Enter email for updates',
  menuItems: [],
}

export default function App() {
  const [site, setSite] = useState(null)
  const [error, setError] = useState(null)
  const [menuOpen, setMenuOpen] = useState(false)
  const [emailOpen, setEmailOpen] = useState(false)

  useEffect(() => {
    let cancelled = false

    fetchSite()
      .then((data) => !cancelled && setSite(data))
      .catch((problem) => !cancelled && setError(problem))

    return () => {
      cancelled = true
    }
  }, [])

  useBodyClass('js-menu-open', menuOpen)
  useBodyClass('js-modal-open', menuOpen)
  useBodyClass('js-email-open', emailOpen)

  // Opening or closing the menu always resets the panel it was showing.
  function toggleMenu() {
    setEmailOpen(false)
    setMenuOpen((open) => !open)
  }

  function closeMenu() {
    setEmailOpen(false)
    setMenuOpen(false)
  }

  const settings = site?.settings ?? FALLBACK_SETTINGS
  const works = site?.works ?? []

  return (
    <>
      <a
        href={settings.shopUrl}
        target="_blank"
        rel="noreferrer"
        className="button fixed top-[24px] right-[13px] sm:top-[30px] sm:right-[30px] z-[11]"
      >
        <span>Shop</span>
      </a>

      <Wordmark className="home-logo ttf duration-300 fixed left-1/2 -translate-x-1/2 top-[16px] sm:top-[18px] z-[20]" />

      <Navigation active={menuOpen} onToggle={toggleMenu} />

      <Menu
        settings={settings}
        emailOpen={emailOpen}
        onOpenEmail={() => setEmailOpen(true)}
        onNavigate={closeMenu}
      />

      {error ? (
        <main className="flex h-[100dvh] flex-col items-center justify-center text-center px-[20px]">
          <p className="mb-[20px]">The site could not be loaded.</p>
          <p className="opacity-50">{error.message}</p>
        </main>
      ) : (
        <Routes>
          <Route path="/" element={<Mophonik settings={settings} works={works} />} />
          <Route path="/mophonik/:slug" element={<WorkDetail works={works} loading={!site} />} />
          <Route path="*" element={<Mophonik settings={settings} works={works} />} />
        </Routes>
      )}
    </>
  )
}
