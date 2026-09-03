/**
 * Placeholder wordmark. The original artwork (`assets/utopia-wordmark.svg`)
 * spells "utopia", so it cannot carry the new name — this is set type until
 * real Mophonik artwork exists. Drop an SVG in here and the layout still works.
 */
export default function Wordmark({ className = '' }) {
  return (
    <div className={`mophonik-logo mophonik ${className}`}>
      <span>Mophonik</span>
    </div>
  )
}
