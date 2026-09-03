import { useEffect, useRef, useState } from 'react'

const GLYPHS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789#%&*'

function scramble(text, revealed) {
  return text
    .split('')
    .map((character, index) => {
      if (index < revealed || character === ' ') {
        return character
      }

      return GLYPHS[Math.floor(Math.random() * GLYPHS.length)]
    })
    .join('')
}

/**
 * Reveals a string one character at a time while the remaining characters keep
 * cycling through random glyphs — the shuffle effect used for the headings and
 * the carousel caption.
 */
export default function useScrambleText(text, { speed = 40, holdFrames = 2 } = {}) {
  const [state, setState] = useState({ source: text, output: text })
  const frame = useRef(0)

  // Restart the reveal in the same render the text changes, so the old caption
  // is never shown next to the new slide.
  if (state.source !== text) {
    setState({ source: text, output: scramble(text ?? '', 0) })
  }

  useEffect(() => {
    if (!text) {
      return undefined
    }

    frame.current = 0
    const total = text.length * holdFrames

    const timer = setInterval(() => {
      frame.current += 1

      if (frame.current > total) {
        clearInterval(timer)
        setState({ source: text, output: text })

        return
      }

      setState({ source: text, output: scramble(text, Math.floor(frame.current / holdFrames)) })
    }, speed)

    return () => clearInterval(timer)
  }, [text, speed, holdFrames])

  return state.output
}
