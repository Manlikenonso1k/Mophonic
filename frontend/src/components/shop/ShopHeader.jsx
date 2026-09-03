import { Link } from 'react-router-dom'

/** Eyebrow + title block, matching the carousel page's header treatment. */
export default function ShopHeader({ eyebrow, title, backTo, backLabel = 'Back' }) {
  return (
    <header className="mb-2">
      {backTo ? (
        <Link to={backTo} className="button bare mb-6 inline-flex">
          <span>← {backLabel}</span>
        </Link>
      ) : null}

      <p className="shop-eyebrow">{eyebrow}</p>
      <h1 className="shop-title">{title}</h1>
    </header>
  )
}
