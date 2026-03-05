/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./public/**/*.{php,html,js}",
        "./lib/**/*.{php,html,js}",
    ],
    theme: {
        extend: {
            borderRadius: {
                'xl': '1rem',
                '2xl': '1.5rem',
            }
        },
    },
    plugins: [],
}
