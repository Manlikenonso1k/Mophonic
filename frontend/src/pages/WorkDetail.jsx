import { Link, useParams } from 'react-router-dom'

export default function WorkDetail({ works, loading }) {
  const { slug } = useParams()
  const work = works.find((item) => item.slug === slug)

  if (!work) {
    return (
      <main className="flex h-[100dvh] flex-col items-center justify-center text-center px-[20px]">
        <p>{loading ? 'Loading…' : 'Not found'}</p>
        {loading ? null : (
          <Link to="/" className="button mt-[20px]">
            <span>Back</span>
          </Link>
        )}
      </main>
    )
  }

  return (
    <main className="relative flex h-[100dvh] flex-col items-center justify-center overflow-hidden">
      {work.video ? (
        <video
          src={work.video}
          autoPlay
          muted
          loop
          playsInline
          aria-hidden="true"
          className="absolute inset-0 w-full h-full object-cover opacity-50"
        />
      ) : null}

      <Link to="/" className="button bare fixed top-[24px] left-[5px] sm:top-[30px] sm:left-[30px] z-[10]">
        <span>Back</span>
      </Link>

      <div className="relative z-[5] flex flex-col items-center text-center px-[20px] perspective">
        <div className="js-parallax ttf duration-[444ms]">
          {work.cover ? (
            <img src={work.cover} alt={work.title} className="h-[41vh] sm:h-[50vh] w-auto rounded-[5px]" />
          ) : null}
        </div>

        <h1 className="mt-[30px] text-[24px] tracking-[1px] uppercase">{work.title}</h1>

        {work.description ? (
          <p className="mt-[10px] max-w-[460px] opacity-80 whitespace-pre-wrap">{work.description}</p>
        ) : null}
      </div>
    </main>
  )
}
