import { useEffect } from 'react'

/** Toggles a class on <body>, which is what the ported CSS animates against. */
export default function useBodyClass(className, active) {
  useEffect(() => {
    document.body.classList.toggle(className, Boolean(active))

    return () => document.body.classList.remove(className)
  }, [className, active])
}
