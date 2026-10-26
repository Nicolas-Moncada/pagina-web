import type { Config } from "tailwindcss";

const config: Config = {
  content: [
    "./*.php",
    "./js/**/*.js",
    "./src/**/*.{js,ts,jsx,tsx,mdx}",
  ],
  theme: {
    extend: {
      colors: {
        campo: {
          50: "#f2f8f3",
          100: "#e1efe4",
          200: "#c4dfcb",
          300: "#99c6a4",
          400: "#6aa778",
          500: "#498b58",
          600: "#366e43",
          700: "#2d5737",
          800: "#26462e",
          900: "#203a27",
          950: "#0d1f13",
        },
        tierra: {
          50: "#faf6f1",
          100: "#f2eae0",
          200: "#e4d3c0",
          300: "#d3b79a",
          400: "#c09774",
          500: "#af7e56",
          600: "#996748",
          700: "#7c503b",
          800: "#664234",
          900: "#54372e",
        },
        leche: {
          50: "#ffffff",
          100: "#fcfbf7",
          200: "#f7f5ed",
          300: "#ede8d8",
        },
        dorado: {
          400: "#f59e0b",
          500: "#d97706",
          600: "#b45309",
        },
      },
      minHeight: {
        touch: "48px",
      },
      minWidth: {
        touch: "48px",
      },
    },
  },
  plugins: [],
};

export default config;
