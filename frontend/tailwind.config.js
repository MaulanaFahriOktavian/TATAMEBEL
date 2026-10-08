/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          cream: '#F8F8F7',
          sand: '#EFEFEF',
          accent: '#191A1C',
          accentHover: '#2A2C2F',
          dark: '#191A1C',
          charcoal: '#222325',
          muted: '#76777B',
          surface: '#FFFFFF',
          border: '#E3E2E4',
        },
      },
      fontFamily: {
        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
        display: ['"Plus Jakarta Sans"', 'sans-serif'],
      },
      borderRadius: {
        '4xl': '2rem',
        '5xl': '2.5rem',
        'arch': '2.75rem',
      },
    },
  },
  plugins: [],
}
