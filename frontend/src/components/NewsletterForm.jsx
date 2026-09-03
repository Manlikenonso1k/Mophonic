import { useState } from 'react'
import { subscribe } from '../api'

export default function NewsletterForm({ heading, termsUrl }) {
  const [email, setEmail] = useState('')
  const [accepted, setAccepted] = useState(false)
  const [status, setStatus] = useState('idle')
  const [message, setMessage] = useState(null)

  const invalid = status === 'invalid' || status === 'error'

  async function onSubmit(event) {
    event.preventDefault()

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) || !accepted) {
      setStatus('invalid')
      setMessage(accepted ? 'Enter a valid email' : 'Please accept the terms')
      window.setTimeout(() => setStatus('idle'), 600)

      return
    }

    setStatus('sending')

    try {
      await subscribe({ email, terms: accepted })
      setStatus('done')
      setMessage('Thanks — you are on the list')
    } catch (problem) {
      setStatus('error')
      setMessage(problem.message)
    }
  }

  if (status === 'done') {
    return <p className="text-center">{message}</p>
  }

  return (
    <form
      onSubmit={onSubmit}
      noValidate
      autoComplete="off"
      spellCheck="false"
      className={invalid ? 'js-invalid' : undefined}
    >
      <span className="mb-[20px] block">{heading}</span>

      <div>
        <label htmlFor="newsletter-email" className="sr-only">
          {heading}
        </label>
        <input
          id="newsletter-email"
          type="email"
          name="newsletter-email"
          className="input"
          placeholder="Email address"
          value={email}
          onChange={(event) => setEmail(event.target.value)}
        />
      </div>

      <div className="my-[20px]">
        <label htmlFor="signup-terms-checkbox-modal" className="flex relative items-center justify-center">
          <div className="relative">
            <input
              id="signup-terms-checkbox-modal"
              type="checkbox"
              name="terms"
              className="w-[18px] h-[18px]"
              checked={accepted}
              onChange={(event) => setAccepted(event.target.checked)}
            />
          </div>
          <div className="ml-[10px]">
            I accept the{' '}
            <a
              href={termsUrl}
              target="_blank"
              rel="noreferrer"
              className="sm:hover:opacity-50 ttf duration-100 underline"
            >
              terms
            </a>
          </div>
        </label>
      </div>

      <button type="submit" className="button large w-full" disabled={status === 'sending'}>
        <span>{status === 'sending' ? 'Sending…' : 'Submit'}</span>
      </button>

      {invalid && message ? <p className="mt-[10px] opacity-50">{message}</p> : null}
    </form>
  )
}
