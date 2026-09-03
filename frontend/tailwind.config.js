/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,jsx}'],
  theme: {
    extend: {
      colors: {
        overlay: 'rgba(0, 0, 0, 0.85)',
      },
      fontFamily: {
        heading: 'var(--font-basquiat)',
        news: 'var(--font-news)',
      },
    },
  },
  plugins: [],
}
