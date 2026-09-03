import { useState } from 'react'
import BackgroundVideos from '../components/BackgroundVideos'
import WorkCarousel from '../components/WorkCarousel'
import useParallaxTilt from '../hooks/useParallaxTilt'
import useScrambleText from '../hooks/useScrambleText'

export default function Mophonik({ settings, works }) {
  const [activeIndex, setActiveIndex] = useState(0)

  useParallaxTilt()

  const eyebrow = useScrambleText(settings.eyebrow)
  const heading = useScrambleText(settings.heading)
  const caption = useScrambleText(works[activeIndex]?.title ?? '')

  return (
    <main className="flex h-[100dvh] flex-col items-center justify-center">
      <div className="uppercase text-center flex h-[26.5dvh] sm:h-[25vh] absolute top-0 items-center pt-[64px] sm:pt-[82px] z-[10] pointer-events-none">
        <div>
          <div>{eyebrow}</div>
          <h2 className="text-[24px] tracking-[1px]">{heading}</h2>
        </div>
      </div>

      <BackgroundVideos works={works} activeIndex={activeIndex} />

      <WorkCarousel works={works} onChange={setActiveIndex} />

      <div className="absolute bottom-0 uppercase ttf text-center z-[10] flex items-center h-[26.5dvh] pb-[86px] sm:h-[25vh] sm:pb-[100px] pointer-events-none">
        <h3>{caption}</h3>
      </div>
    </main>
  )
}
