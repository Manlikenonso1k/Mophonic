import { Link } from 'react-router-dom'
import NewsletterForm from './NewsletterForm'
import Wordmark from './Wordmark'

export default function Menu({ settings, emailOpen, onOpenEmail, onNavigate }) {
  const items = settings.menuItems ?? []

  return (
    <div className="js-menu menu bg-overlay fixed inset-0 ttf flex items-center justify-center z-[19]">
      <Wordmark className="fixed left-1/2 -translate-x-1/2 top-[16px] sm:top-[18px]" />

      <nav className="ttf duration-[444ms] w-full flex justify-center">
        <ul>
          {items.map((item) => (
            <li key={`${item.label}-${item.url}`} className="py-[20px] sm:py-[30px] md:py-[45px] flex justify-center">
              {item.external ? (
                <a className="button large" href={item.url} target="_blank" rel="noreferrer">
                  <span>{item.label}</span>
                </a>
              ) : (
                <Link className="button large" to={item.url} onClick={onNavigate}>
                  <span>{item.label}</span>
                </Link>
              )}
            </li>
          ))}

          <li className="py-[15px] sm:py-[30px] flex justify-center">
            <button type="button" className="button large bare" onClick={onOpenEmail}>
              <span>Email</span>
            </button>
          </li>
        </ul>
      </nav>

      <div
        className="flex items-center justify-center js-email-modal absolute inset-0 ttf duration-[444ms]"
        aria-hidden={!emailOpen}
      >
        <div className="text-center w-[90%] max-w-[420px]">
          <NewsletterForm heading={settings.newsletterHeading} termsUrl={settings.termsUrl} />
        </div>
      </div>
    </div>
  )
}
