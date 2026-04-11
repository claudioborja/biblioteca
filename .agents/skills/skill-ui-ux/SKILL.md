---
name: skill-ui-ux
description: "**WORKFLOW SKILL** — Professional UI/UX design and implementation at expert level. USE FOR: creating pixel-perfect, fully responsive interfaces; design systems and component libraries; accessible and performant UI (WCAG 2.1 AA/AAA); implementing with top-tier frameworks (React, Next.js, Vue, Nuxt, Svelte, Angular); CSS frameworks (Tailwind CSS, shadcn/ui, Radix UI, Material UI, Chakra UI, Mantine); fluid layouts and mobile-first responsive design; design tokens, typography scales, color systems; micro-interactions and animations; dark mode; component architecture. INVOKES: file system tools, codebase exploration subagents, design token generation. DO NOT USE FOR: backend logic unrelated to UI; database queries; server-side processing."
---

# UI/UX — Professional Design & Implementation

## Core Philosophy

- **Mobile-first**: All layouts start at the smallest viewport and scale up.
- **Accessibility first**: WCAG 2.1 AA minimum, AAA where feasible. Every component is keyboard-navigable and screen-reader friendly.
- **Performance by default**: Core Web Vitals (LCP < 2.5s, CLS < 0.1, INP < 200ms). Lazy load, code-split, optimize assets.
- **Design system driven**: Tokens for color, spacing, typography, radius, shadow. No magic numbers.
- **Framework agnostic mindset**: Deliver idiomatic code for whatever framework is in use — React, Vue, Svelte, Angular, or vanilla.

---

## Decision Flow

| Need | Recommended Approach |
|------|----------------------|
| Component UI | shadcn/ui + Radix UI primitives + Tailwind |
| Full design system | Custom tokens + Tailwind CSS config + component library |
| Motion / animation | Framer Motion (React), GSAP, or CSS transitions |
| Forms | React Hook Form + Zod / VeeValidate (Vue) |
| Icons | Lucide, Heroicons, Phosphor |
| Data visualization | Recharts, Victory, Chart.js, D3 |
| Theming / dark mode | CSS custom properties + next-themes / Nuxt Color Mode |
| Headless primitives | Radix UI, Headless UI, Ark UI |
| Storybook | When building a shared component library |

---

## Responsive Design Standard

Always implement **fluid, breakpoint-aware** layouts. Default breakpoint scale (Tailwind-aligned):

| Breakpoint | Min Width | Target Devices |
|------------|-----------|----------------|
| `xs` (base) | 0px | Small phones |
| `sm` | 640px | Large phones |
| `md` | 768px | Tablets |
| `lg` | 1024px | Small laptops |
| `xl` | 1280px | Desktops |
| `2xl` | 1536px | Large screens |

**Rules:**
- Use `clamp()` for fluid typography: `font-size: clamp(1rem, 2.5vw, 1.5rem)`.
- Use CSS Grid for 2D layouts, Flexbox for 1D.
- Avoid fixed pixel widths on containers — use `max-w-*` + `w-full`.
- Test every UI at 320px, 768px, 1024px, and 1440px minimum.

---

## Design Token System

When building or extending a design system, generate tokens for:

```
colors/
  - brand (primary, secondary, accent)
  - semantic (success, warning, error, info)
  - neutral scale (50–950)
  - surface (background, card, overlay)
  - text (default, muted, inverse, disabled)

spacing/       — 4px base unit (0.25rem increments)
typography/    — scale (xs, sm, base, lg, xl, 2xl, 3xl, 4xl, 5xl)
               — weights (regular 400, medium 500, semibold 600, bold 700)
               — line heights, letter spacing
radius/        — none, sm, md, lg, xl, full
shadows/       — xs, sm, md, lg, xl
transitions/   — fast (100ms), base (200ms), slow (400ms)
z-index/       — base, dropdown, sticky, modal, tooltip, toast
```

Expose tokens as CSS custom properties and map them into the framework's config (e.g. `tailwind.config.ts`).

---

## Component Architecture

Structure components following **Atomic Design**:

```
atoms/        — Button, Input, Label, Badge, Avatar, Icon
molecules/    — FormField, Card, Dropdown, SearchBar, Tooltip
organisms/    — Navbar, Sidebar, DataTable, Modal, Toaster, Hero
templates/    — PageLayout, DashboardLayout, AuthLayout
pages/        — Assembled from templates + organisms
```

**Component contract (every component must have):**
- Typed props (TypeScript / JSDoc)
- Accessible ARIA roles and labels
- Keyboard interaction pattern (Enter, Space, Escape, Arrow keys as applicable)
- Focus visible outline (`outline-none focus-visible:ring-2`)
- Responsive variants
- Dark mode support via semantic color tokens
- Loading / empty / error states where applicable

---

## Accessibility Checklist

Apply to every component and page:

- [ ] Color contrast ≥ 4.5:1 for normal text, ≥ 3:1 for large text (WCAG AA)
- [ ] All interactive elements reachable via Tab; logical focus order
- [ ] `aria-label`, `aria-describedby`, `aria-expanded`, `role` set correctly
- [ ] Images have meaningful `alt` text; decorative images use `alt=""`
- [ ] Forms: `<label>` linked to inputs; errors announced via `aria-live`
- [ ] Modals trap focus; Escape closes them; focus returns on close
- [ ] No motion for users with `prefers-reduced-motion`
- [ ] Skip-to-content link at top of page
- [ ] Landmark regions: `<header>`, `<nav>`, `<main>`, `<footer>`

---

## Framework-Specific Guidance

### React / Next.js
- Use **Server Components** by default; add `"use client"` only for interactivity.
- Prefer `next/image` and `next/font` for performance.
- State: `useState` → Zustand → Jotai (in order of complexity).
- Styling: Tailwind CSS + `cn()` (clsx + tailwind-merge) utility.
- Components: shadcn/ui as base, customized via CSS variables.

### Vue / Nuxt
- Composition API (`<script setup>`) exclusively.
- Styling: Tailwind CSS or UnoCSS + Nuxt UI / Shadcn Vue.
- State: Pinia.
- `nuxt/image` for optimized images.

### Svelte / SvelteKit
- Use Tailwind CSS + shadcn-svelte.
- Prefer Svelte stores for state; avoid unnecessary reactivity.
- Transitions and animations with Svelte's built-in `transition:` / `animate:`.

### Angular
- Standalone components (no NgModules unless required).
- Angular Material or PrimeNG as component base.
- Signals for state management.
- Tailwind CSS via PostCSS integration.

---

## Animation & Motion

- **Purposeful motion only**: Animate to communicate state, guide attention, or provide feedback — not decoration.
- Default easing: `ease-out` for entrances, `ease-in` for exits, `ease-in-out` for transitions.
- Duration ranges: micro-interactions 100–200ms, page transitions 300–500ms.
- Always respect `prefers-reduced-motion`:
  ```css
  @media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
      animation-duration: 0.01ms !important;
      transition-duration: 0.01ms !important;
    }
  }
  ```
- Use Framer Motion `AnimatePresence` for enter/exit animations in React.

---

## Dark Mode

- Implement via CSS custom properties + class strategy (`.dark` on `<html>`).
- Never use hard-coded colors — always reference semantic tokens.
- Test both modes before delivering any component.
- Use `next-themes` (Next.js), Nuxt Color Mode, or a custom `prefers-color-scheme` media query.

---

## Performance Standards

| Metric | Target |
|--------|--------|
| LCP | < 2.5s |
| CLS | < 0.1 |
| INP | < 200ms |
| FCP | < 1.8s |
| TTI | < 3.5s |

Practices to enforce:
- Lazy load images and below-fold components.
- Use `loading="lazy"` and `decoding="async"` on all non-critical images.
- Avoid layout shifts: define `width` and `height` on images; use skeleton loaders.
- Tree-shake icon libraries (import individually, not the full package).
- Minimize JavaScript bundle size; prefer CSS over JS for animations.
- Virtualize long lists (react-window, TanStack Virtual).

---

## Workflow

1. **Understand context** — Read existing code/styles before proposing changes. Identify the active framework, CSS approach, and any design system already in use.
2. **Audit first** — On refactors, list what exists before changing it.
3. **Tokens before components** — Establish or extend the design token layer first.
4. **Build bottom-up** — Atoms → Molecules → Organisms → Pages.
5. **Responsive at every step** — Never build desktop-only and retrofit mobile.
6. **Accessibility inline** — Add ARIA and keyboard support while building, not after.
7. **Dark mode inline** — Use semantic tokens so dark mode works without extra passes.
8. **Deliver clean code** — No commented-out blocks, no dead styles, no inline styles unless dynamic.

---

## Common Patterns Reference

### Utility: `cn()` (Tailwind class merging)
```ts
import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}
```

### Fluid typography scale
```css
:root {
  --text-xs:   clamp(0.75rem,  1vw,   0.875rem);
  --text-sm:   clamp(0.875rem, 1.5vw, 1rem);
  --text-base: clamp(1rem,     2vw,   1.125rem);
  --text-lg:   clamp(1.125rem, 2.5vw, 1.25rem);
  --text-xl:   clamp(1.25rem,  3vw,   1.5rem);
  --text-2xl:  clamp(1.5rem,   4vw,   2rem);
  --text-3xl:  clamp(1.875rem, 5vw,   2.5rem);
  --text-4xl:  clamp(2.25rem,  6vw,   3rem);
}
```

### Accessible visually-hidden utility
```css
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}
```

### Focus visible pattern
```css
:focus-visible {
  outline: 2px solid var(--color-brand-primary);
  outline-offset: 2px;
  border-radius: var(--radius-sm);
}
```
