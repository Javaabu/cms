import { defineConfig } from "vitest/config"

const path = (relative: string) => new URL(relative, import.meta.url).pathname

// The javaabu/* React code comes from vendor/ (composer install first), as
// javaabu/manifest's Vite plugin resolves it in apps. The block editor's
// renderer is replaced by a small stand-in (tests/js/shims): the real one needs TipTap.
export default defineConfig({
  resolve: {
    alias: [
      { find: /^@javaabu\/cms$/, replacement: path("./resources/js/index.ts") },
      { find: /^@javaabu\/cms\/(.*)$/, replacement: path("./resources/js/$1") },
      { find: /^@javaabu\/post-types\/(.*)$/, replacement: path("./vendor/javaabu/post-types/resources/js/$1") },
      { find: /^@javaabu\/widgets\/(.*)$/, replacement: path("./vendor/javaabu/widgets/resources/js/$1") },
      {
        find: /^@javaabu\/dynamic-menubuilder\/(.*)$/,
        replacement: path("./vendor/javaabu/dynamic-menubuilder/resources/js/$1"),
      },
      { find: /^@javaabu\/block-editor\/react$/, replacement: path("./tests/js/shims/block-editor-react.tsx") },
    ],
  },
  test: {
    environment: "jsdom",
    include: ["tests/js/**/*.test.{ts,tsx}"],
    setupFiles: ["tests/js/setup.ts"],
    coverage: {
      provider: "v8",
      include: ["resources/js/**/*.{ts,tsx}"],
      reporter: ["text", "clover", "lcov"],
      reportsDirectory: "coverage",
    },
  },
})
