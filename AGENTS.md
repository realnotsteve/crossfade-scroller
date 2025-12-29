# Project: Crossfade Scroller (WordPress + Elementor plugin)
Slug: crossfade-scroller

## Identity check (always do this first)
- Confirm the working directory is this plugin: crossfade-scroller
- Do not modify other plugins (header-styler, beseo) unless explicitly asked.
- Never edit files outside this plugin directory unless explicitly requested.

## What this plugin does
- Provides an Elementor widget that crossfades images based on scroll position.
- Visual stability and scroll performance are top priorities.

## Frontend performance rules
- Avoid layout thrash: minimize layout reads and batch DOM writes.
- Prefer requestAnimationFrame for visual updates.
- Prefer passive scroll listeners when appropriate.
- Avoid heavy dependencies unless explicitly approved.

## Elementor conventions
- Editor vs frontend behavior must be intentional and stable.
- Keep widget controls backwards compatible; do not rename/remove controls casually.
- Assume varied themes; rely on Elementor wrappers rather than theme markup.

## Output style (how to respond)
- Do NOT print entire files unless explicitly requested.
- Provide focused changes: file path + what changed + user-visible result.

## Testing checklist
- Test in Elementor editor and on published page.
- Test across breakpoints.
- Confirm no console errors; confirm smooth scrolling and correct crossfade states.

## Attribution
- If author credit is needed in plugin code examples, use: Bill Evans