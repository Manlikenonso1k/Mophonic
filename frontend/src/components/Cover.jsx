import { useState } from 'react'
import { Link } from 'react-router-dom'

const DEFAULT_RATIO = 0.8237

/** One poster in the carousel, with the hover "Explore" overlay. */
export default function Cover({ work }) {
  const [ratio, setRatio] = useState(DEFAULT_RATIO)
  const [loaded, setLoaded] = useState(false)

  const external = /^https?:\/\//i.test(work.href ?? '')

  const overlayClasses =
    'absolute inset-0 flex items-center justify-center ttf sm:opacity-0 hover:opacity-100 duration-200 sm:flex sm:bg-black sm:bg-opacity-50 rounded-[5px] z-[1]'

  const overlay = (
    <div className="hidden sm:flex">
      <div className="button no-blur">Explore</div>
    </div>
  )

  return (
    <div
      className={`image-container h-[41vh] sm:h-[50vh] mx-auto relative ${loaded ? 'is-loaded' : ''}`}
      style={{ aspectRatio: ratio }}
    >
      {work.cover ? (
        <img
          src={work.cover}
          alt={work.title}
          className="image-vertical rounded-[5px]"
          style={{ color: 'transparent', width: '100%', height: 'auto' }}
          onLoad={(event) => {
            const { naturalWidth, naturalHeight } = event.currentTarget

            if (naturalWidth && naturalHeight) {
              setRatio(naturalWidth / naturalHeight)
            }

            setLoaded(true)
          }}
        />
      ) : null}

      {work.href ? (
        external ? (
          <a className={overlayClasses} href={work.href} target="_blank" rel="noreferrer">
            {overlay}
          </a>
        ) : (
          <Link className={overlayClasses} to={work.href}>
            {overlay}
          </Link>
        )
      ) : null}

      <div
        className="js-loader absolute rounded-[5px] inset-0 ttf duration-300 flex items-center justify-center"
        style={{ backgroundColor: 'rgba(0, 0, 0, 0.5)' }}
      >
        <div className="w-[50px] h-[50px] relative">
          <div className="loader is-loading" />
        </div>
      </div>
    </div>
  )
}
