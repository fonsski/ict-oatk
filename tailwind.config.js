/** @type {import('tailwindcss').Config} */

// Палитра системы. Старые шаблоны написаны на стандартных классах
// (bg-blue-600, text-gray-500, border-slate-200), поэтому тона подменены
// здесь: так весь интерфейс переходит на единые токены без правки
// каждого файла. Нейтральные ряды (gray, slate, zinc, neutral, stone)
// указывают на одну шкалу «чернил», а синий, индиго и фиолетовый — на
// единственный акцент системы: приглушённый бирюзовый.
const ink = {
    50: "#f9fbfc",
    100: "#f4f6f8",
    200: "#e8eaec",
    300: "#cdced1",
    400: "#737577",
    500: "#5d5f60",
    600: "#4a4c4e",
    700: "#353638",
    800: "#222325",
    900: "#131416",
    950: "#0b0b0c",
};

const accent = {
    50: "#eef6f8",
    100: "#d8eaef",
    200: "#b3d5de",
    300: "#85b9c8",
    400: "#3a859e",
    500: "#25738a",
    600: "#1b6c80",
    700: "#155a6b",
    800: "#134a58",
    900: "#113f4a",
    950: "#0a2a33",
};

// Зелёный и красный подтянуты до контраста 4.5:1 как текст на белом.
const green = {
    50: "#f0faf4",
    100: "#dcfae5",
    200: "#b9f0cc",
    300: "#86dfa8",
    400: "#4cc07e",
    500: "#2b9a62",
    600: "#1e7b4f",
    700: "#1a6342",
    800: "#0b3d22",
    900: "#04250e",
    950: "#021a09",
};

const red = {
    50: "#fff7f6",
    100: "#ffedea",
    200: "#ffd3cd",
    300: "#f9a59b",
    400: "#ee6c5f",
    500: "#dc3a37",
    600: "#c4242a",
    700: "#a31d22",
    800: "#831c1e",
    900: "#5e1618",
    950: "#3a0b0d",
};

// Оранжевый, жёлтый и янтарный: текстовые тона (600–700) тёмные, чтобы
// предупреждения читались на белом; светлые тона для заливок остались.
const orange = {
    50: "#fff7ed",
    100: "#ffedd5",
    200: "#fed7aa",
    300: "#fdba74",
    400: "#fb923c",
    500: "#c2570c",
    600: "#a94b0a",
    700: "#8a3d0b",
    800: "#703310",
    900: "#5c2c12",
    950: "#33160a",
};
const yellow = {
    50: "#fefce8",
    100: "#fef9c3",
    200: "#fef08a",
    300: "#fde047",
    400: "#facc15",
    500: "#ca8a04",
    600: "#a16207",
    700: "#854d0e",
    800: "#713f12",
    900: "#5c3410",
    950: "#352006",
};
const amber = {
    50: "#fffbeb",
    100: "#fef3c7",
    200: "#fde68a",
    300: "#fcd34d",
    400: "#fbbf24",
    500: "#d97706",
    600: "#b45309",
    700: "#92400e",
    800: "#78350f",
    900: "#5c2c10",
    950: "#341608",
};

export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
        "./app/**/*.php",
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: [
                    '"IBM Plex Sans"',
                    "Inter",
                    "system-ui",
                    "-apple-system",
                    "Segoe UI",
                    "Roboto",
                    "sans-serif",
                ],
                mono: [
                    '"IBM Plex Mono"',
                    "ui-monospace",
                    "SFMono-Regular",
                    "Menlo",
                    "monospace",
                ],
            },
            colors: {
                gray: ink,
                slate: ink,
                zinc: ink,
                neutral: ink,
                stone: ink,
                blue: accent,
                sky: accent,
                cyan: accent,
                indigo: accent,
                violet: ink,
                purple: ink,
                green,
                emerald: green,
                red,
                rose: red,
                orange,
                yellow,
                amber,
            },
            // Стандартные радиусы ужаты: карточки 8px, элементы управления 6px.
            borderRadius: {
                sm: "4px",
                DEFAULT: "6px",
                md: "6px",
                lg: "8px",
                xl: "8px",
                "2xl": "12px",
                "3xl": "16px",
            },
            // Покоящиеся поверхности без теней: глубину даёт шаг тона.
            // Тени остались только у того, что парит: меню, окна, подсказки.
            boxShadow: {
                sm: "none",
                DEFAULT: "none",
                md: "none",
                lg: "0 8px 24px rgba(19, 20, 22, 0.10)",
                xl: "0 12px 32px rgba(19, 20, 22, 0.12)",
                "2xl": "0 16px 40px rgba(19, 20, 22, 0.14)",
            },
        },
    },
    plugins: [
        require("@tailwindcss/forms"),
        require("@tailwindcss/typography"),
    ],
};
