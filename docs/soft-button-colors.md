# Softer Action Button Colors (Matching Badge Colors)

## Overview

This document describes the implementation of softer, more readable colors for Filament action buttons. The button colors now **exactly match Filament's badge colors** for a cohesive, beautiful design. Both **light mode** and **dark mode** are supported.

## File Modified
**`app/Providers/Filament/AdminPanelProvider.php`** (Lines 377-566)

CSS rules added to the inline `<style>` block within `renderHook('panels::head.end')`.

---

## Light Mode Color Reference

| Button Type | Background | Border | Text |
|:---|:---|:---|:---|
| **Info/Primary** | `rgb(239, 246, 255)` | `rgb(191, 219, 254)` | `rgb(37, 99, 235)` |
| **Success** | `rgb(240, 253, 244)` | `rgb(187, 247, 208)` | `rgb(22, 163, 74)` |
| **Danger** | `rgb(254, 242, 242)` | `rgb(254, 202, 202)` | `rgb(220, 38, 38)` |
| **Warning** | `rgb(255, 251, 235)` | `rgb(253, 230, 138)` | `rgb(217, 119, 6)` |
| **Gray** | `rgb(250, 250, 250)` | `rgb(228, 228, 231)` | `rgb(82, 82, 91)` |

---

## Dark Mode Color Reference

| Button Type | Background | Border | Text |
|:---|:---|:---|:---|
| **Info/Primary** | `rgba(59 130 246 / 0.15)` | `rgba(59 130 246 / 0.3)` | `rgb(147, 197, 253)` |
| **Success** | `rgba(34 197 94 / 0.15)` | `rgba(34 197 94 / 0.3)` | `rgb(134, 239, 172)` |
| **Danger** | `rgba(239 68 68 / 0.15)` | `rgba(239 68 68 / 0.3)` | `rgb(252, 165, 165)` |
| **Warning** | `rgba(245 158 11 / 0.15)` | `rgba(245 158 11 / 0.3)` | `rgb(253, 230, 138)` |
| **Gray** | `rgba(161 161 170 / 0.15)` | `rgba(161 161 170 / 0.3)` | `rgb(212, 212, 216)` |

---

## CSS Code

### Light Mode

```css
/* --- SOFTER ACTION BUTTON COLORS (Matching Badge Colors) --- */

/* Info/Primary buttons - Soft blue (matches badge info) */
.fi-btn.fi-color-info.bg-custom-600,
.fi-btn.fi-btn-color-info.bg-custom-600,
.fi-btn.fi-color-primary.bg-custom-600,
.fi-btn.fi-btn-color-primary.bg-custom-600 {
    background-color: rgb(239 246 255) !important;
    color: rgb(37 99 235) !important;
    border: 1px solid rgb(191 219 254) !important;
}
.fi-btn.fi-color-info.bg-custom-600:hover,
.fi-btn.fi-btn-color-info.bg-custom-600:hover,
.fi-btn.fi-color-primary.bg-custom-600:hover,
.fi-btn.fi-btn-color-primary.bg-custom-600:hover {
    background-color: rgb(219 234 254) !important;
    border-color: rgb(147 197 253) !important;
}

/* Success buttons - Soft green (matches badge success) */
.fi-btn.fi-color-success.bg-custom-600,
.fi-btn.fi-btn-color-success.bg-custom-600 {
    background-color: rgb(240 253 244) !important;
    color: rgb(22 163 74) !important;
    border: 1px solid rgb(187 247 208) !important;
}

/* Danger buttons - Soft red (matches badge danger) */
.fi-btn.fi-color-danger.bg-custom-600,
.fi-btn.fi-btn-color-danger.bg-custom-600 {
    background-color: rgb(254 242 242) !important;
    color: rgb(220 38 38) !important;
    border: 1px solid rgb(254 202 202) !important;
}

/* Warning buttons - Soft amber (matches badge warning) */
.fi-btn.fi-color-warning.bg-custom-600,
.fi-btn.fi-btn-color-warning.bg-custom-600 {
    background-color: rgb(255 251 235) !important;
    color: rgb(217 119 6) !important;
    border: 1px solid rgb(253 230 138) !important;
}

/* Gray buttons - Soft gray (matches badge gray) */
.fi-btn.fi-color-gray.bg-custom-600,
.fi-btn.fi-btn-color-gray.bg-custom-600 {
    background-color: rgb(250 250 250) !important;
    color: rgb(82 82 91) !important;
    border: 1px solid rgb(228 228 231) !important;
}
```

### Dark Mode

```css
/* ============================================= */
/* DARK MODE BUTTON COLORS                       */
/* ============================================= */

/* Info/Primary buttons - Dark mode */
.dark .fi-btn.fi-color-info.bg-custom-600,
.dark .fi-btn.fi-btn-color-info.bg-custom-600,
.dark .fi-btn.fi-color-primary.bg-custom-600,
.dark .fi-btn.fi-btn-color-primary.bg-custom-600 {
    background-color: rgba(59 130 246 / 0.15) !important;
    color: rgb(147 197 253) !important;
    border: 1px solid rgba(59 130 246 / 0.3) !important;
}
.dark .fi-btn.fi-color-info.bg-custom-600:hover,
.dark .fi-btn.fi-btn-color-info.bg-custom-600:hover,
.dark .fi-btn.fi-color-primary.bg-custom-600:hover,
.dark .fi-btn.fi-btn-color-primary.bg-custom-600:hover {
    background-color: rgba(59 130 246 / 0.25) !important;
    border-color: rgba(59 130 246 / 0.5) !important;
}

/* Success buttons - Dark mode */
.dark .fi-btn.fi-color-success.bg-custom-600,
.dark .fi-btn.fi-btn-color-success.bg-custom-600 {
    background-color: rgba(34 197 94 / 0.15) !important;
    color: rgb(134 239 172) !important;
    border: 1px solid rgba(34 197 94 / 0.3) !important;
}

/* Danger buttons - Dark mode */
.dark .fi-btn.fi-color-danger.bg-custom-600,
.dark .fi-btn.fi-btn-color-danger.bg-custom-600 {
    background-color: rgba(239 68 68 / 0.15) !important;
    color: rgb(252 165 165) !important;
    border: 1px solid rgba(239 68 68 / 0.3) !important;
}

/* Warning buttons - Dark mode */
.dark .fi-btn.fi-color-warning.bg-custom-600,
.dark .fi-btn.fi-btn-color-warning.bg-custom-600 {
    background-color: rgba(245 158 11 / 0.15) !important;
    color: rgb(253 230 138) !important;
    border: 1px solid rgba(245 158 11 / 0.3) !important;
}

/* Gray buttons - Dark mode */
.dark .fi-btn.fi-color-gray.bg-custom-600,
.dark .fi-btn.fi-btn-color-gray.bg-custom-600 {
    background-color: rgba(161 161 170 / 0.15) !important;
    color: rgb(212 212 216) !important;
    border: 1px solid rgba(161 161 170 / 0.3) !important;
}
```

---

## Technical Notes

### Why Inline CSS?
Filament buttons use CSS variables and classes like `fi-btn fi-color-custom bg-custom-600`. This application loads styles via `AdminPanelProvider.php`'s `renderHook('panels::head.end')` rather than external `theme.css`.

### Design Rationale
- **Colors match badges** - Consistent visual language across the UI
- **Soft pastel backgrounds** - Reduces visual intensity and eye strain  
- **Dark contrasting text** - Excellent readability
- **Subtle borders** - Added definition without harshness
- **Hover states** - Slightly darker on hover for feedback

### Dark Mode Strategy
- **Background**: Semi-transparent (15% opacity) version of the main color
- **Text**: Light/pastel version of each color for readability on dark backgrounds
- **Border**: Semi-transparent (30% opacity) for subtle definition
- **Hover**: Slightly more opaque (25% bg, 50% border) for feedback

### Affected Areas
- Header actions on view pages (Proses, Selesai, Batal, Edit)
- Table row actions and bulk actions
- Form submit/cancel buttons
- Modal confirmation buttons

---

*Last updated: 2026-01-27*
