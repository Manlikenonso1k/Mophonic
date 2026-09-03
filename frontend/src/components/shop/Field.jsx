/**
 * A labelled field with its error rendered underneath and wired up with
 * aria-describedby, so the message reaches screen readers with the input.
 */
export default function Field({
  id,
  label,
  error,
  as = 'input',
  required = false,
  ...props
}) {
  const Tag = as
  const errorId = `${id}-error`

  return (
    <div className={`field ${error ? 'has-error' : ''}`}>
      <label htmlFor={id}>
        {label}
        {required ? ' *' : ''}
      </label>

      <Tag
        id={id}
        className="input"
        aria-invalid={error ? 'true' : undefined}
        aria-describedby={error ? errorId : undefined}
        required={required}
        {...props}
      />

      {error ? (
        <p className="field-error" id={errorId}>
          {error}
        </p>
      ) : null}
    </div>
  )
}
