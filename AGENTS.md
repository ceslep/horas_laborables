# AGENTS.md - Project Guidelines for AI Agents

This document provides guidelines for AI agents working on this codebase.

## Project Overview

- **Project Name**: horas_laborables
- **Type**: Svelte 5 + TypeScript + Vite web application
- **Purpose**: Employee hours registration system for teachers at Instituto Guática
- **Stack**: Svelte 5, TypeScript, Vite, Tailwind CSS v4, Vitest

## Build/Lint/Test Commands

### Development
```bash
npm run dev          # Start development server
```

### Build
```bash
npm run build       # Build for production (outputs to dist/)
npm run preview     # Preview production build locally
npm run deploy      # Build and deploy to GitHub Pages
```

### Type Checking
```bash
npm run check       # Run svelte-check + TypeScript compiler
```

### Testing
```bash
npm run test              # Run all tests in watch mode (Vitest)
npm run test -- --run     # Run tests once (not watch mode)
npm run test date-validation.test.ts    # Run single test file
npm run test "mes anterior"  # Run tests matching pattern
```
- Test files: `src/tests/*.test.ts`
- Use `describe`, `it`, `expect` from vitest
- Use `beforeEach` for setup
- Mock external dependencies (Google Sheets service, etc.)
- Test business logic in isolation from UI

## Code Style Guidelines

### General Principles
- Use **Svelte 5 runes** (`$state`, `$derived`, `$effect`, `$props`) instead of Svelte 4 reactivity
- Write **TypeScript** for all new code; avoid JavaScript unless necessary
- Keep components small and focused on single responsibility

### Naming Conventions
- **Components**: PascalCase (e.g., `CalendarCell.svelte`, `TeacherSelector.svelte`)
- **Functions/variables**: camelCase (e.g., `handleSelect`, `hoursData`)
- **Constants**: UPPER_SNAKE_CASE for config values (e.g., `API_CONFIG`)
- **Interfaces/Types**: PascalCase with descriptive names (e.g., `Festivo`, `Teacher`)
- **Svelte files**: `.svelte` extension with `<script lang="ts">`

### TypeScript Guidelines
- Always define return types for functions when not obvious
- Use `Record<K, V>` for dictionary-like objects
- Use `interface` for public APIs, `type` for unions/intersections
- Use `any[]` for loosely typed arrays (e.g., categories array in CalendarCell)
- Enable `strict: true` equivalent via svelte-check

### Svelte 5 Patterns

#### Props Definition (use Props interface)
```svelte
<script lang="ts">
  interface Props {
    day: number;
    value: string;
    onSelect: (val: string) => void;
    categories: any[];
    isCurrentMonth?: boolean;
    weekday?: string;
  }

  let {
    day,
    value,
    onSelect,
    categories,
    isCurrentMonth = true,
    weekday = "",
  }: Props = $props();
</script>
```

#### State and Derived
```svelte
<script lang="ts">
  // Reactive state
  let showMenu = $state(false);
  
  // State with type annotation
  let buttonRef = $state<HTMLButtonElement | null>(null);
  
  // Typed state for objects
  let menuPosition = $state<{
    top: number;
    left: number | "auto";
    right: number | "auto";
  }>({ top: 0, left: 0, right: "auto" });

  // Derived computed value
  const selectedCategory = $derived(categories.find((c) => c.id === value));
</script>
```

#### Effect for Side Effects
```svelte
<script lang="ts">
  $effect(() => {
    if (teacherName && month) loadExistingRecords();
  });
</script>
```

### Imports
- Order imports: Svelte built-ins → External libraries → Internal components/services → Assets
- Use explicit relative imports (e.g., `./CalendarCell.svelte`, not `@/components/...`)
- Group by type with blank lines between groups

### Component Structure
- Place interface Props at the top of `<script>` block
- Use `$state` for all reactive variables
- Use `$derived` for computed values
- Use `$effect` for side effects (data loading, subscriptions)
- Keep template logic minimal; extract to functions

### Error Handling
- Use try/catch with meaningful error messages
- Display errors to users via SweetAlert2 or custom notification
- Log errors to console with context for debugging
- Handle server errors with user-friendly messages in Spanish

### Tailwind CSS
- Use utility classes directly in templates (v4 style)
- Use semantic color names (slate, blue, emerald, etc.)
- Responsive design: use `sm:`, `md:`, `lg:`, `xl:` prefixes
- Common patterns: `flex`, `grid`, `p-4`, `text-sm`, `bg-white`, `border-slate-200`

### File Organization
```
src/
├── lib/
│   ├── components/     # Reusable Svelte components
│   ├── services/      # API services (.ts and .svelte.ts files)
│   ├── constants.ts   # Configuration constants
│   ├── utils.ts       # Utility functions
│   └── festivos.ts    # Holiday data
├── tests/             # Test files (*.test.ts)
├── assets/            # Static assets
├── App.svelte         # Root component
└── main.ts            # Entry point
```

### Git Conventions
- Commit messages in Spanish or English, be descriptive
- Don't commit secrets (API keys go in environment variables)
- Build output in `dist/` is committed for GitHub Pages deployment

## Key Dependencies

- **svelte**: ^5.43.8 (Svelte 5 with runes)
- **typescript**: ~5.9.3
- **vite**: ^7.2.4
- **vitest**: ^4.0.18
- **tailwindcss**: ^4.1.18
- **chart.js**: ^4.5.1
- **sweetalert2**: ^11.26.17

## Environment Notes

- Base path for GitHub Pages: `/horas_laborables/`
- Backend: PHP scripts at external URL (configured in `src/lib/constants.ts`)
- Timezone: America/Bogota for all date operations

## No Cursor/Copilot Rules Found

This project does not have custom Cursor rules (`.cursor/rules/`) or Copilot instructions (`.github/copilot-instructions.md`).
