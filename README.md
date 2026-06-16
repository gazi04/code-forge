# CodeForge

> A gamified computer-science learning platform for kids aged 7–16.

CodeForge turns learning to code into an adventure. Students journey through themed **Worlds**, work through **Courses** and **Lessons** made of interactive challenge **blocks**, and earn XP, coins, levels, and streaks along the way. They climb Redis-backed leaderboards, spend coins on cosmetics and boosts in a store, and unlock achievements. Educators and admins author all the content through a Filament dashboard.

---

## Tech stack

**Backend**
- PHP 8.3+ (8.5 in dev) · [Laravel 13](https://laravel.com)
- [Fortify](https://laravel.com/docs/fortify) — authentication backend
- [Filament 5](https://filamentphp.com) — admin dashboard
- [Wayfinder](https://github.com/laravel/wayfinder) — typed route functions for the frontend
- Redis — leaderboard sorted sets
- Spatie Activitylog + EloquentSortable

**Frontend**
- [Inertia v3](https://inertiajs.com) + [Svelte 5](https://svelte.dev)
- [Tailwind CSS v4](https://tailwindcss.com)
- Vite

**Tooling**
- [Pest 4](https://pestphp.com) / PHPUnit 12 — tests
- Laravel Pint — PHP formatting · PHPStan — static analysis
- ESLint + Prettier — JS/Svelte
- Laravel Boost · Pail · Sail

---

## Architecture

### Content hierarchy

```
World  (themed environment)
  └── Course  (topic, min_level_requirement gate, optional prerequisite)
        └── Lesson  (one unit, xp_reward + coin_reward)
              └── blocks[]  (typed interactive content cells)
```

Blocks live in `lessons.blocks` as a JSON array of `{ type, data }` objects. Seven block types exist:

| Type | Purpose |
|---|---|
| `text_content` | Reading / explanation |
| `quiz` | Single/multi-choice question |
| `code_challenge` | Write & run code (client-side execution) |
| `labyrinth_challenge` | Navigate a maze (client-side win-state) |
| `sequence_challenge` | Order steps correctly |
| `bughunt_challenge` | Find and fix buggy lines |
| `variable_matching_challenge` | Match pairs |

Block completion is tracked per-user-per-index in `block_submissions`; whole-lesson completion in `lesson_submissions`. Answer keys are stripped from the client payload by `BlockSanitizer` and verified server-side by `BlockValidator` before any reward is granted.

### Two surfaces

| Surface | Stack | Auth |
|---|---|---|
| Student-facing | Inertia v3 + Svelte 5 + Tailwind v4 | `auth` + `student` middleware, `student.` route prefix |
| Admin dashboard | Filament v5 | `role === 'admin'` via `canAccessPanel()` |

Student pages live in `resources/js/pages/Student/`; reusable components in `resources/js/components/` (block-specific ones in `resources/js/components/Blocks/`).

### Gamification engine

All XP/coins/level/streak logic runs through a single entry point — `ProgressionService::processVictory(User, int $xp, int $coins)`. It handles streaks (consecutive days, freeze mechanic, rested-XP pool), XP multipliers, level-up detection (`UserLeveledUp` event), and increments the Redis leaderboards (`leaderboard:all_time`, `leaderboard:weekly`) — skipped for shadowbanned users.

### Theming

Each World has a `ThemePack` whose `config` JSON drives CSS custom properties (`--primary-color`, `--surface-color`, `--bg-color`, …) applied by `StudentLayout.svelte`. Components never hardcode hex values — they read these vars.

### Leaderboard

Redis sorted sets keyed by user id. `LeaderboardController` returns the top 50 enriched with DB data plus the player's own rank. The weekly set resets every Monday midnight via the `app:reset-weekly-leaderboard` scheduled command; `app:rebuild-leaderboard` rebuilds the all-time set from `users.xp` after any Redis flush.

### User roles

`User.role` is `admin` or `student`. Admins use Filament; students use the Inertia frontend. Shadowbanned users (`is_shadowbanned = true`) keep using the platform but are hidden from leaderboards.

---

## Getting started

### Prerequisites

- PHP 8.3+, Composer
- Node.js + npm
- Redis (required for leaderboards)

### Setup

```bash
# Install deps, create .env, generate key, migrate, build assets — one shot
composer setup

# Seed demo content (worlds, courses, lessons, store items, theme packs, leaderboard)
php artisan db:seed
```

### Run the dev environment

```bash
# Laravel server + Vite + queue worker + Pail logs, all together
composer run dev
```

Make sure Redis is running, or leaderboard features (and their tests) will be unavailable.

---

## Common commands

| Command | What it does |
|---|---|
| `composer run dev` | Full dev environment (server + Vite + queue + logs) |
| `php artisan test --compact` | Run the test suite |
| `php artisan test --compact --filter=Name` | Run a single test / filter |
| `composer run lint` | Fix PHP formatting (Pint) |
| `composer run lint:check` | Check PHP formatting (no write) |
| `npm run format` / `npm run format:check` | Prettier write / check |
| `npm run lint` / `npm run lint:check` | ESLint fix / check |
| `npm run types:check` | Svelte type check |
| `composer run ci:check` | Full CI gate (lint + format + types + tests) |
| `npm run build` | Build frontend assets |
| `php artisan app:rebuild-leaderboard` | Rebuild the all-time leaderboard from `users.xp` |
| `php artisan app:reset-weekly-leaderboard` | Reset the weekly leaderboard (scheduled Mondays) |

After changing any PHP file, run `vendor/bin/pint --dirty` before committing.

---

## Project structure

```
app/
  Http/Controllers/        Student-facing controllers (Lesson, Course, Store, Leaderboard, …)
  Services/                ProgressionService, BlockValidator
  Listeners/               EvaluateAchievements, HandleWorldCompletion, …
  Policies/                CoursePolicy (role + publish + level + prerequisite gating)
  Filament/Resources/*/    Admin resources — Pages/ + Schemas/ + Tables/ + RelationManagers/
  Concerns/                Shared controller traits (ResolvesEquippedItems, …)
resources/js/
  pages/Student/           Inertia pages
  components/Blocks/        Per-block-type Svelte components
database/
  seeders/                 World/Course/Lesson/StoreItem/ThemePack/Leaderboard seeders
  migrations/
```

Every Filament resource follows the same directory pattern (`Pages/`, `Schemas/`, `Tables/`, `RelationManagers/`) — check sibling resources before adding a new one.

---

## Testing

Tests are written with **Pest** and are feature-test first. Run the whole suite with `php artisan test --compact`, or narrow with `--filter`:

```bash
php artisan test --compact --filter=Leaderboard
```

Some leaderboard tests skip automatically when Redis is unavailable.
