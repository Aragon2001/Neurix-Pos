Thanks for the thorough write-up @bsshreesha — your root-cause analysis is spot on, and you even guessed the correct storage key (`lte-theme`). Reproduced and fixed. 👍

As you noted, the color-mode script lives in `_scripts.astro`, which is loaded at the **end of `<body>`**. So the browser paints the default (light) theme first, then the script swaps `data-bs-theme` once it runs — hence the flash for dark-mode users on every load.

**Fix:** I added a small render-blocking inline script to `_head.astro` (shared by every page), essentially your suggestion with a few refinements:

```html
<script is:inline>
  ;(() => {
    "use strict"
    const STORAGE_KEY = "lte-theme"
    let stored = null
    try {
      stored = localStorage.getItem(STORAGE_KEY)
    } catch {
      // localStorage may be unavailable (private mode, sandboxed iframe).
    }
    const prefersDark = globalThis.matchMedia(
      "(prefers-color-scheme: dark)"
    ).matches
    const resolved =
      stored === "dark" || stored === "light"
        ? stored
        : prefersDark
          ? "dark"
          : "light"
    document.documentElement.setAttribute("data-bs-theme", resolved)
    document.documentElement.style.colorScheme = resolved
  })()
</script>
```

Refinements vs. the snippet in the issue:
- **Mirrors the resolution already in `_scripts.astro`** so the stored `"auto"` value (and the unset case) consistently falls back to `prefers-color-scheme` — keeps the head and body logic in sync.
- **`try/catch` around `localStorage`** so it degrades gracefully where storage access throws (private mode, sandboxed iframes) instead of leaving the page un-themed.
- Placed right after `<title>`, before the stylesheet, so `data-bs-theme` is set before the first paint.

**Verified:** with `lte-theme=dark` set, a probe reading `document.documentElement.getAttribute("data-bs-theme")` at the very first line of `<body>` already returns `"dark"` — i.e. the theme is applied before any body content is laid out, so there's no flash. Pages now render directly in the stored theme on refresh.

Changed in `src/html/components/_head.astro` and the rebuilt `dist` (applies to all pages). It'll be in the next release. Thanks again for the detailed report and suggested fix!
