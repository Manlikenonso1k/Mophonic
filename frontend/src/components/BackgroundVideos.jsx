import { useEffect, useRef } from 'react'

/**
 * Full screen looping videos, one per work. Only the slide in view plays; the
 * neighbours are preloaded so a swipe never lands on a black frame.
 */
export default function BackgroundVideos({ works, activeIndex }) {
  const videos = useRef([])

  useEffect(() => {
    videos.current.forEach((video, index) => {
      if (!video) {
        return
      }

      if (index === activeIndex) {
        const played = video.play()

        if (played) {
          played.catch(() => {})
        }
      } else {
        video.pause()
      }
    })
  }, [activeIndex, works.length])

  return (
    <div className="mophonik-background inset-0 overflow-hidden bg-black">
      {works.map((work, index) => {
        if (!work.video) {
          return null
        }

        const near = Math.abs(index - activeIndex) <= 1

        return (
          <video
            key={work.id}
            ref={(element) => {
              videos.current[index] = element
            }}
            src={work.video}
            preload={near ? 'auto' : 'none'}
            playsInline
            muted
            loop
            aria-hidden="true"
            className={`ease-in duration-[888ms] transition-opacity absolute w-full h-screen top-0 left-0 object-cover ${
              index === activeIndex ? 'opacity-50' : 'opacity-0'
            }`}
          />
        )
      })}
    </div>
  )
}
