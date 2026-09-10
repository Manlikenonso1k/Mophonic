export default function QuantityStepper({ value, onChange, max = 99, label = 'Quantity' }) {
  return (
    <div className="stepper" role="group" aria-label={label}>
      <button
        type="button"
        onClick={(event) => {
          event.preventDefault()
          event.stopPropagation()
          onChange(value - 1)
        }}
        aria-label="Decrease quantity"
      >
        −
      </button>
      <span className="count" aria-live="polite">
        {value}
      </span>
      <button
        type="button"
        onClick={(event) => {
          event.preventDefault()
          event.stopPropagation()
          onChange(value + 1)
        }}
        disabled={value >= max}
        aria-label="Increase quantity"
      >
        +
      </button>
    </div>
  )
}
