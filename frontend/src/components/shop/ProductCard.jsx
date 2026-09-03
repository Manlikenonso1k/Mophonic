import { Link } from 'react-router-dom'
import { formatKobo } from '../../money'

export default function ProductCard({ product, onAdd }) {
  return (
    <article className="product-card">
      <Link to={`/shop/${product.slug}`} className="frame relative block">
        {product.image ? (
          <img src={product.image} alt={product.name} loading="lazy" width="900" height="1125" />
        ) : null}
        {product.inStock ? null : <span className="sold-out">Sold out</span>}
      </Link>

      <div className="body">
        <Link to={`/shop/${product.slug}`} className="ttf duration-200 hover:opacity-70">
          {product.name}
        </Link>

        {product.unitLabel ? <span className="muted">{product.unitLabel}</span> : null}

        <div className="mt-auto flex items-center justify-between pt-3 gap-2">
          <span className="price">{formatKobo(product.priceKobo)}</span>

          <button
            type="button"
            className="button no-blur"
            onClick={() => onAdd(product)}
            disabled={!product.inStock}
          >
            <span>{product.inStock ? 'Add' : 'Sold out'}</span>
          </button>
        </div>
      </div>
    </article>
  )
}
