import { useEffect, useState } from 'react'
import { useLocation } from 'react-router-dom'
import logoMark from '../assets/logo-mark.svg'

/** The circular button at the bottom of the screen that opens the menu. */
export default function Navigation({ active, onToggle }) {
  const [loaded, setLoaded] = useState(false)
  const { pathname } = useLocation()

  // The carousel is a fixed stage with nothing to tap underneath, so the
  // button sits centre stage there. Every other page scrolls, and anything
  // fixed over a scrolling page eventually covers a control — the Add buttons
  // in the grid, the Download receipt button on a confirmation. On those
  // routes it sits in the top chrome band, which page content never occupies.
  const overContent = pathname !== '/' && !pathname.startsWith('/mophonik/')

  useEffect(() => {
    const timer = window.setTimeout(() => setLoaded(true), 1333)

    return () => window.clearTimeout(timer)
  }, [])

  return (
    <header>
      <button
        type="button"
        onClick={onToggle}
        aria-label={active ? 'Close menu' : 'Open menu'}
        aria-expanded={active}
        className={[
          'navigation rounded-full fixed z-20 ttf select-none is-animating',
          overContent
            ? 'is-docked w-[56px] h-[56px] top-[14px] left-[14px]'
            : 'w-[70px] h-[70px] bottom-[16px] sm:bottom-[30px] left-[50%]',
          loaded ? 'is-loaded' : '',
          active ? 'is-active' : '',
        ].join(' ')}
      >
        <img src={logoMark} alt="" className="ttf logo" />

        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="64"
          height="63"
          viewBox="0 0 64 63"
          fill="none"
          className="ttf close"
        >
          <line x1="20.3209" y1="19.5" x2="43.6138" y2="42.7929" stroke="white" strokeLinecap="round" strokeDasharray="2 2" />
          <line x1="43.6138" y1="20.2071" x2="20.3209" y2="43.5" stroke="white" strokeLinecap="round" strokeDasharray="2 2" />
        </svg>

        <div className="loader" />
      </button>
    </header>
  )
}
