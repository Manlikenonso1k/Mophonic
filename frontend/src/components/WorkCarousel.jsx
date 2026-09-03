import { useEffect, useRef } from 'react'
import Swiper from 'swiper'
import { Keyboard, Mousewheel } from 'swiper/modules'
import 'swiper/css'
import Cover from './Cover'

/**
 * Re-creation of the original "swiper-carousel" effect: slides stack up to the
 * left of the active one, each pushed a little further and scaled down, while
 * the wrapper itself keeps sliding normally.
 */
function applyEffect(swiper) {
  const size = swiper.slidesSizesGrid?.[0] || swiper.width || 1
  const current = -swiper.translate / size

  swiper.slides.forEach((slide, index) => {
    const progress = index - current
    const distance = Math.abs(progress)
    const offset = -(50 * distance + 15 * distance * (distance - 1)) * Math.sign(progress)
    const scale = Math.max(0, 1 - 0.5 * distance)

    slide.style.transform = `translateX(${offset}%) scale(${scale})`
    slide.style.zIndex = String(100 - Math.round(distance * 10))
    slide.style.opacity = distance > 3 ? '0' : '1'

    const fading = slide.querySelector('.js-fade')

    if (fading) {
      fading.style.opacity = String(Math.max(0, Math.min(1, 1 - distance / 3)))
    }
  })
}

function applyTransition(swiper, duration) {
  swiper.slides.forEach((slide) => {
    slide.style.transitionDuration = `${duration}ms`

    const fading = slide.querySelector('.js-fade')

    if (fading) {
      fading.style.transitionDuration = `${duration}ms`
    }
  })
}

export default function WorkCarousel({ works, onChange }) {
  const containerRef = useRef(null)
  const swiperRef = useRef(null)
  const onChangeRef = useRef(onChange)

  useEffect(() => {
    onChangeRef.current = onChange
  }, [onChange])

  useEffect(() => {
    if (!containerRef.current || works.length === 0) {
      return undefined
    }

    const swiper = new Swiper(containerRef.current, {
      modules: [Keyboard, Mousewheel],
      slidesPerView: 1,
      speed: 888,
      watchSlidesProgress: true,
      grabCursor: true,
      keyboard: { enabled: true },
      mousewheel: { forceToAxis: true, thresholdDelta: 25 },
      on: {
        init: applyEffect,
        setTranslate: applyEffect,
        setTransition: applyTransition,
        resize: applyEffect,
        slideChange: (instance) => onChangeRef.current?.(instance.activeIndex),
      },
    })

    swiperRef.current = swiper

    return () => {
      swiper.destroy(true, true)
      swiperRef.current = null
    }
  }, [works.length])

  return (
    <div ref={containerRef} className="swiper swiper-carousel w-full h-[100dvh]">
      <div className="swiper-wrapper">
        {works.map((work) => (
          <div className="swiper-slide w-full" key={work.id}>
            <div className="h-[100dvh] flex items-center justify-center w-full perspective">
              <div className="js-fade js-parallax w-full ttf duration-[444ms]">
                <Cover work={work} />
              </div>
            </div>
          </div>
        ))}
      </div>

      <button
        type="button"
        onClick={() => swiperRef.current?.slidePrev()}
        className="absolute top-0 left-0 w-[10vw] sm:w-[24vw] h-full z-[9] cursor-w-resize"
      >
        <span className="sr-only">Previous slide</span>
      </button>
      <button
        type="button"
        onClick={() => swiperRef.current?.slideNext()}
        className="absolute top-0 right-0 w-[10vw] sm:w-[24vw] h-full z-[9] cursor-e-resize"
      >
        <span className="sr-only">Next slide</span>
      </button>
    </div>
  )
}
