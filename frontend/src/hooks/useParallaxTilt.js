import { useEffect } from 'react'

/**
 * Tilts every `.js-parallax` element towards the pointer, the way the covers
 * lean on the original page. Skipped on touch devices.
 */
export default function useParallaxTilt({ maxY = 8, maxX = 5 } = {}) {
  useEffect(() => {
    if (window.matchMedia('(pointer: coarse)').matches) {
      return undefined
    }

    let frame = null

    const onMove = (event) => {
      if (frame) {
        return
      }

      frame = window.requestAnimationFrame(() => {
        frame = null

        const x = event.clientX / window.innerWidth - 0.5
        const y = event.clientY / window.innerHeight - 0.5
        const transform = `rotateY(${(x * maxY * 2).toFixed(3)}deg) rotateX(${(-y * maxX * 2).toFixed(3)}deg)`

        document.querySelectorAll('.js-parallax').forEach((element) => {
          element.style.transform = transform
        })
      })
    }

    window.addEventListener('mousemove', onMove)

    return () => {
      window.removeEventListener('mousemove', onMove)

      if (frame) {
        window.cancelAnimationFrame(frame)
      }
    }
  }, [maxY, maxX])
}
