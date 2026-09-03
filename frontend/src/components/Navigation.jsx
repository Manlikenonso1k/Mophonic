import { useEffect, useState } from 'react'
import logoMark from '../assets/logo-mark.svg'

/** The circular button at the bottom of the screen that opens the menu. */
export default function Navigation({ active, onToggle }) {
  const [loaded, setLoaded] = useState(false)

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
          'navigation w-[70px] h-[70px] rounded-full fixed bottom-[16px] sm:bottom-[30px] left-[50%] z-20 ttf select-none is-animating',
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
