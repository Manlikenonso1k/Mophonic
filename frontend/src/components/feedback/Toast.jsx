/**
 * Fixed above the fold and pointer-events:none, so it confirms without ever
 * standing between a thumb and the next control, and without moving the page.
 */
export default function Toast({ message }) {
  if (!message) {
    return null
  }

  return (
    <div
      className="mo-toast pointer-events-none fixed bottom-[104px] left-1/2 z-[12] -translate-x-1/2"
      role="status"
      aria-live="polite"
    >
      <span className="button no-blur">{message}</span>
    </div>
  )
}
