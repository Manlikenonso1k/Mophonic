import { useEffect, useRef, useState } from 'react'

const COLOURS = ['#d1552e', '#e2924a', '#f3f3f3', '#c64234']

/**
 * A one-shot burst on a canvas. Deliberately small: 60 pieces, transform-only
 * drawing, one rAF loop that stops itself after ~2.6s. It sits behind a
 * pointer-events:none layer so it can never intercept a tap, and it renders
 * nothing at all when the viewer asks for reduced motion.
 */
export default function Confetti({ pieces = 60, duration = 2600 }) {
  const canvasRef = useRef(null)
  // Asked for less motion: render nothing at all, not even the canvas.
  const [allowed] = useState(
    () => !window.matchMedia('(prefers-reduced-motion: reduce)').matches,
  )

  useEffect(() => {
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    const canvas = canvasRef.current

    if (reduced || !canvas) {
      return undefined
    }

    const context = canvas.getContext('2d')
    // Cap the backing store: a 3x DPR phone gains nothing here and pays for it.
    const scale = Math.min(window.devicePixelRatio || 1, 2)
    const width = canvas.clientWidth
    const height = canvas.clientHeight

    canvas.width = width * scale
    canvas.height = height * scale
    context.scale(scale, scale)

    const confetti = Array.from({ length: pieces }, () => ({
      x: width / 2 + (Math.random() - 0.5) * width * 0.4,
      y: height * 0.28 + Math.random() * 40,
      vx: (Math.random() - 0.5) * 5,
      vy: -3 - Math.random() * 5,
      size: 4 + Math.random() * 5,
      spin: (Math.random() - 0.5) * 0.3,
      angle: Math.random() * Math.PI,
      colour: COLOURS[Math.floor(Math.random() * COLOURS.length)],
    }))

    let frame = null
    const started = performance.now()

    const draw = (now) => {
      const elapsed = now - started
      const life = 1 - elapsed / duration

      context.clearRect(0, 0, width, height)

      if (life <= 0) {
        frame = null

        return
      }

      context.globalAlpha = Math.min(1, life * 1.6)

      for (const piece of confetti) {
        piece.x += piece.vx
        piece.y += piece.vy
        piece.vy += 0.16
        piece.angle += piece.spin

        context.save()
        context.translate(piece.x, piece.y)
        context.rotate(piece.angle)
        context.fillStyle = piece.colour
        context.fillRect(-piece.size / 2, -piece.size / 2, piece.size, piece.size * 0.6)
        context.restore()
      }

      frame = window.requestAnimationFrame(draw)
    }

    frame = window.requestAnimationFrame(draw)

    return () => {
      if (frame) {
        window.cancelAnimationFrame(frame)
      }

      context.clearRect(0, 0, width, height)
    }
  }, [pieces, duration])

  if (!allowed) {
    return null
  }

  return (
    <canvas
      ref={canvasRef}
      aria-hidden="true"
      className="pointer-events-none fixed inset-0 z-[13] h-full w-full"
    />
  )
}
