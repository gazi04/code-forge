# CodeForge — Project Analysis Report

**Date:** 2026-06-11
**Scope:** Backend controllers, services, listeners, models, migrations, routes, and key frontend block components.

---
## 1. Critical Bugs

> **Fix audit (2026-06-14):** 1.1 ✅ DONE · 1.2 ✅ DONE · 1.3 ✅ DONE · 1.4 ✅ DONE · 1.5 ✅ DONE · 1.6 ✅ DONE · 1.7 ✅ DONE · 1.8 ✅ DONE · 1.9 ✅ DONE
### ~~1.1 Unvalidated `blockIndex` allows infinite XP/coin farming~~
**Status: ✅ DONE.** `submitBlockClaim` now rejects out-of-range indices (`LessonController.php:160-162` → `abort(404)`), and the route enforces `->whereNumber('blockIndex')` (`routes/web.php:72`).
**File:** `app/Http/Controllers/LessonController.php:154-206` (`submitBlockClaim`)
The controller never validates that `$blockIndex` corresponds to a real block in the lesson. Any index (e.g. `999`, `1000`, …) passes the "anti-cheat" check because the unique constraint is per `(user, lesson, block_index)`, and a missing block silently falls back to the default reward:

```php
$blockData = $blocks[$blockIndex]['data'] ?? [];   // [] for any out-of-range index
$xpReward = $blockData['xp_reward'] ?? 15;          // still awards 15 XP
```

A student can POST `/lessons/{slug}/blocks/999999/claim`, `.../999998/claim`, etc. and farm unlimited XP and coins.
**Fix:** Reject the claim when `$blockIndex < 0 || $blockIndex >= count($lesson->blocks ?? [])` (and consider rejecting claims for `text_content` blocks that need no solving). Also add `->whereNumber('blockIndex')` to the route.
### 1.2 All challenge answers are shipped to the client; server never validates solutions
**Status: ✅ DONE (core 4 challenge types).** Answer keys are now stripped from the client payload by `App\Support\BlockSanitizer` (wired into `LessonResource`): quiz `is_correct`/`feedback` removed; sequence shipped as shuffled `items` (no canonical order); bughunt lines expose only shuffled `choices` (no `correct_text`/decoys); matching split into independent shuffled `left_items`/`right_items` (no pairing); code_challenge `solution_code` + hidden test outputs removed. Server-side validation runs in `App\Services\BlockValidator`, called from `LessonController::submitBlockClaim` before any reward — a wrong/forged answer returns `game_result.status = 'incorrect'` and writes no `BlockSubmission`. Frontend reworked to submit the student's answer and trust the server response (`claimMicroReward(slug, index, answer, { onCorrect, onIncorrect })`); the four interactive blocks gained explicit Verify buttons. Covered by `tests/Feature/LessonSubmissionTest.php` + `tests/Unit/BlockSanitizerTest.php`. **Out of scope (still client-trusted):** `labyrinth_challenge` win-state and `code_challenge` test execution validate in the browser (they require client-side simulation/Pyodide); only their answer keys are stripped, not the correctness check.
**Files:** `app/Http/Resources/LessonResource.php:26`, `resources/js/components/Blocks/QuizBlock.svelte`, `SequenceBlock.svelte`, `BugHuntBlock.svelte`, etc.
`LessonResource` sends the raw `blocks` JSON — including `is_correct` flags, `correct_sequence`, `correct_text` — to the browser. Correctness is checked purely client-side, and the claim endpoints (`submitBlockClaim`, `submitClaim`) award rewards without ever verifying an answer. Anyone can:
- Read the correct answers from the Inertia page props (View Source), or
- Skip solving entirely and POST directly to the claim endpoints.
**Fix:** Validate submissions server-side (send the user's answer with the claim and check it against the block data), and strip answer keys (`is_correct`, `correct_text`, `correct_sequence`, …) from the payload sent to the client.
### 1.3 `users.name` is not unique, but is used as the identity key in several places
**Status: ✅ DONE.** Redis member key switched from `$user->name` to `$user->id` everywhere (`ProgressionService.php:135-136`, `LeaderboardController.php:23-58`, `UsersTable.php:88-91`, `LeaderboardSeeder.php:48-49`). `LeaderboardController` now enriches `whereIn('id', ...)`/`keyBy('id')` and resolves display name at render time (skipping orphaned members). Public profile route bound by id (`/u/{user}`); profile share link uses `route('public.profile.show', $user)`. Name intentionally left non-unique (kids may share names). NOTE: pre-existing Redis sets keyed by name are now stale — flush/reseed (`db:seed --class=LeaderboardSeeder`) or add the 2.7 rebuild command on deploy.
**Files:** `database/migrations/0001_01_01_000000_create_users_table.php`, `app/Services/ProgressionService.php:134-135`, `app/Http/Controllers/LeaderboardController.php:26`, `routes/web.php` (`/u/{user:name}`)
Only `email` is unique. Yet:
- Redis leaderboards use `$user->name` as the sorted-set member → two students named "Alex" share (and corrupt) one score.
- `LeaderboardController` enriches via `User::whereIn('name', ...)->keyBy('name')` → collisions resolve to an arbitrary user's level/avatar.
- Public profiles bind by name (`/u/{user:name}`) → returns whichever matching user comes first; names containing `/`, `#`, or `?` produce broken URLs.
**Fix:** Use `user_id` as the Redis member key (resolve names at render time) and for public-profile routing (or add a unique username/slug column).
### 1.4 `lesson_submissions.lesson_id` stores the slug; `block_submissions.lesson_id` stores the FK id
**Status: ✅ DONE.** Migration `2026_06_14_migrate_lesson_submissions_lesson_id_to_foreign_key` backfills slug→id, drops old string column, adds FK+cascade with new unique `(user_id, lesson_id)`. All code updated: `LessonController` (show/submitClaim/checkWorldCompletion), `CourseController` (removed pipe hack), `EvaluateAchievements`, `LessonFunnelWidget`. `LessonSubmission` now has `lesson()` relation; profile ledger shows lesson name instead of raw id.
**Files:** `database/migrations/2026_05_29_194635_create_lesson_submissions_table.php` (string column, no FK), `app/Http/Controllers/LessonController.php:48,90,116`
Consequences:
- If an admin renames a lesson slug, all completion history for it is orphaned: the lesson shows as incomplete and the student can complete it again for full rewards (the unique key `(user_id, lesson_id)` no longer matches).
- No referential integrity (deleting a lesson leaves dangling rows; `block_submissions` cascades but `lesson_submissions` doesn't).
- Every consumer must translate between id and slug (see the convoluted `pipe()` mapping in `CourseController::show:34-37`), and the profile ledger displays the raw slug as the label (`ProfileController::show:36`).
**Fix:** Migrate `lesson_submissions.lesson_id` to a real `foreignId` referencing `lessons.id`.
### 1.5 Double-reward race in `submitClaim` / `submitBlockClaim`
**Status: ✅ DONE.** Both methods now gate on `createOrFirst` keyed by the unique index (`LessonSubmission` on `(user_id, lesson_id)`, `BlockSubmission` on `(user_id, lesson_id, block_index)`): the insert runs in its own transaction and returns the existing row on a concurrent unique-constraint violation, so only the request that genuinely inserts proceeds (`!wasRecentlyCreated` → `already_completed`, no reward). `processVictory` + the reward-write on the gate row are wrapped in one `DB::transaction`. `submitBlockClaim` keeps its fast-path `exists()` + answer-validation ahead of the gate so a wrong answer never inserts a row (preserves the "no re-validation of cleared block" behavior). Covered by `tests/Feature/LessonSubmissionTest.php` ("awards a block reward only once across repeated claims", "awards a lesson reward only once across repeated submits").
**File:** `app/Http/Controllers/LessonController.php:89-119, 159-198`
The flow is: `exists()` check → `processVictory()` (commits XP/coins in its own transaction) → `create()` submission. Two concurrent submits both pass the `exists()` check, both get XP/coins committed, then the second `create()` throws a unique-constraint exception (500) — but the duplicate XP is already saved and the Redis leaderboard already incremented.
**Fix:** Wrap the submission insert and `processVictory` in one transaction, insert the submission row first (catching the unique violation), or use `firstOrCreate`/`wasRecentlyCreated` as the gate (as `HandleWorldCompletion` correctly does).
### ~~1.6 `submitBlockClaim` skips the level-gate check~~
**Status: ✅ DONE.** `LessonController` now uses `AuthorizesRequests` and calls `$this->authorize('view', $course)` in both `show` and `submitBlockClaim`, reusing `CoursePolicy::view` (role + `is_published` + `min_level_requirement` gate). Below-level or unpublished access returns 403 and writes no reward. Covered by `tests/Feature/LessonSubmissionTest.php` (level gate on view + block claim, unpublished-course block claim).
**File:** `app/Http/Controllers/LessonController.php:154`
`submitClaim` enforces `min_level_requirement`, and `CourseController::show` authorizes via `CoursePolicy`, but `submitBlockClaim` and `LessonController::show` do neither. A student below the required level can open any lesson directly by slug and claim block rewards.
**Fix:** Apply the same policy/level check in `LessonController::show` and `submitBlockClaim`.
### 1.7 Unpublished worlds/courses are visible to students
**Status: ✅ DONE.** `CoursePolicy::view` now denies access when `!$course->is_published` (checked before the level gate). `CourseController::show` loads the course before authorization, so the 403 fires on any unpublished course regardless of slug. Covered by new test in `WorldPublishVisibilityTest`.
**File:** `app/Http/Controllers/WorldController.php:16,26`
`index` and `show` never filter on `is_published` (and load every course regardless of its published flag), while `SearchController` does filter. Draft content leaks to the student UI, and `show` is reachable by slug for any unpublished world. `CourseController::show` also doesn't check `is_published`.
**Fix:** Add `where('is_published', true)` to world/course queries (and to the eager-loaded `courses` relation), or a global scope for the student surface.
### 1.8 Custom student login has no rate limiting
**Status: ✅ DONE.** `POST /login/student` now carries `->middleware('throttle:login')` (`routes/web.php:22-24`), reusing Fortify's named `login` limiter (5/min keyed by email + ip) so both auth paths share one throttle policy. Covered by `tests/Feature/AuthenticationTest.php` ("throttles repeated failed student login attempts" → 6th attempt returns 429).
**File:** `app/Http/Controllers/Auth/StudentLoginController.php:17-40`, `routes/web.php`
Fortify's login pipeline is throttled (`FortifyServiceProvider:78`), but the custom `POST /login/student` route bypasses Fortify entirely and has no `throttle` middleware — an unthrottled password brute-force vector on a platform for children.
**Fix:** Add `->middleware('throttle:5,1')` (or reuse Fortify's `loginRateLimiter`) on `student.login.submit`. Longer term, consolidate on one login path — two parallel auth flows (Fortify + custom) is a maintenance hazard.
### ~~1.9 Admin "Reset Progress" doesn't reset everything it claims~~
**Status: ✅ DONE.** All DB wipes (submissions, achievements, inventory, world completions, prefs, stat columns) now run inside one `DB::transaction`. Reset also zeroes `streak_count`, `streak_freezes`, `rested_xp_balance` and nulls `pending_achievements`. After the transaction commits, `Redis::zrem` drops the student (member key = user id) from both `leaderboard:all_time` and `leaderboard:weekly`. Covered by `tests/Feature/ResetProgressTest.php` (streak/rested/pending reset + Redis zrem on both keys).
**File:** `app/Filament/Resources/Users/Pages/EditUser.php:26-72`
The confirmation modal says it wipes progress "back to baseline defaults," but the action leaves:
- `streak_count`, `streak_freezes`, `rested_xp_balance` untouched,
- `xp_boost_multiplier` / `xp_boost_lessons_remaining` untouched,
- `pending_achievements` untouched (queued achievement toasts will still fire),
- **Redis leaderboard scores untouched** — the reset student keeps their rank on both `leaderboard:all_time` and `leaderboard:weekly`.
It also runs ~7 write operations with no `DB::transaction`, so a mid-way failure leaves half-reset state.
**Fix:** Wrap in a transaction; also reset the streak/boost/pending columns and `Redis::zrem` both leaderboard keys.

---
## 2. High-Priority Issues
### ~~2.1 Store purchase race conditions~~
**Status: ✅ DONE.** `StoreController::purchase` now runs every guard (active, permanent-already-owned, one_time sold-out, coin balance) **and** the mutations inside one `DB::transaction` against row-locked copies (`StoreItem`/`User` re-fetched with `lockForUpdate()`). Coins are spent with an atomic `decrement('coins', $price)` (no read-modify-write), and `sold_count` with `increment` — so concurrent purchases can no longer both pass the checks (no negative coins / oversell). Guards return `['error' => ...]` from the closure and the caller maps it to the same redirect/messages as before. The `one_time` sold-out check now guards `stock_limit !== null` (null = unlimited, made explicit — fixes the falsy-comparison note). `lockForUpdate` is a no-op on SQLite but correct on MySQL/Postgres. Covered by `tests/Feature/StoreTest.php` ("never drives coins negative across repeated purchases at the balance boundary", "allows purchasing a one_time item with no stock limit") plus the 6 existing purchase tests. **Out of scope:** `processVictory`'s own read-modify-write `save()` is unchanged — the atomic `decrement` keeps the purchase side from clobbering it.

**Changes made:**
- `app/Http/Controllers/StoreController.php` — rewrote `purchase()`: wrapped every guard + mutation in one `DB::transaction`; re-fetch both rows with `lockForUpdate()` (`StoreItem::whereKey(...)`, `User::whereKey(...)`); moved all four guards (active, permanent-owned, one_time sold-out, coin balance) inside the tx against the locked rows; spend coins via atomic `decrement('coins', $price)` and bump `sold_count` via `increment` (removed the `$user->coins -= ...; save()` read-modify-write); guards now `return ['error' => ...]` from the closure, caller maps to the same redirect/messages.
- Sold-out check now requires `stock_limit !== null` (null = unlimited, explicit) — fixes the falsy-comparison note.
- Added `use App\Models\User;` import.
- `tests/Feature/StoreTest.php` — +2 tests: coins never go negative across repeated boundary purchases; `one_time` with null `stock_limit` is purchasable. (6 existing purchase tests still pass.)
- Verified: Pint clean; `StoreTest` 16/16; full suite shows no new failures (the lone failure is the pre-existing `users.name` UNIQUE conflict from the concurrent 1.3 work, unrelated).

**File:** `app/Http/Controllers/StoreController.php:55-105`
- The coin balance check (line 63) and stock check (line 77) happen **outside** the transaction, and the user row is not locked. Two concurrent purchases can both pass the check → negative coins / overselling limited-stock items.
- `$user->coins -= $item->price_coins; $user->save();` is a read-modify-write on the whole row. A concurrent `processVictory` for the same user can be lost-updated. (SQLite serializes writes today, but this breaks the moment you move to MySQL/Postgres.)
- `one_time` items: `$item->sold_count >= $item->stock_limit` — if `stock_limit` is `NULL` the comparison is falsy and the item never sells out; verify that's intended.
**Fix:** Move all checks inside the transaction with `$user = User::lockForUpdate()->find(...)`, use `decrement('coins', $item->price_coins)` guarded by a `where('coins', '>=', $price)` conditional update.
### ~~2.2 XP boost multiplier truncated to integer~~
**Status: ✅ DONE.** Multipliers are now fractional end-to-end. New migration `2026_06_14_183921_change_xp_boost_multiplier_to_decimal` changes `users.xp_boost_multiplier` to `decimal(4,2) default 1`; `User` casts it to `float`. `StoreController::activateItem` casts the configured multiplier to `float` (a `1.5×` boost is preserved, no longer truncated to `1×`) and defines **stacking = extend**: activating a boost while one is active keeps the higher multiplier (`max`, never downgrades) and adds the lessons; otherwise it sets both. `ProgressionService::processVictory` now uses `(int) round($earnedXp * $multiplier)` so fractional multipliers don't floor away XP. Covered by `tests/Feature/StoreTest.php` (fractional preserved; stacking keeps stronger multiplier + extends lessons) and `tests/Unit/ProgressionServiceTest.php` (fractional `1.5×` rounds `33→50`; existing boost tests updated to float `2.0`/`1.0`).

**Changes made:**
- `database/migrations/2026_06_14_183921_change_xp_boost_multiplier_to_decimal.php` (new) — `xp_boost_multiplier` → `decimal(4,2) default 1` (down reverts to `unsignedInteger`). Verified up + rollback + re-up on SQLite.
- `app/Models/User.php` — added `'xp_boost_multiplier' => 'float'` cast.
- `app/Http/Controllers/StoreController.php` — `activateItem` xp_boost branch: `(float)` multiplier + `(int)` lessons; extend-stacking via `max()` of current/new multiplier when a boost is already active.
- `app/Services/ProgressionService.php` — `(int) ($earnedXp * $multiplier)` → `(int) round(...)`.
- `tests/Feature/StoreTest.php` (+2 tests, updated 1 to `2.0`), `tests/Unit/ProgressionServiceTest.php` (+1 test, updated 2 to `2.0`/`1.0`).
- Verified: Pint clean; `StoreTest` + `ProgressionServiceTest` 42/42; full suite no new failures (lone failure is the pre-existing `users.name` UNIQUE conflict from concurrent 1.3 work).

**File:** `app/Http/Controllers/StoreController.php:117`

```php
$user->xp_boost_multiplier = (int) ($item->effect_config['multiplier'] ?? 2);
```

A 1.5× boost becomes 1× (the item does nothing). Also, activating a second boost while one is active **overwrites** the multiplier but **adds** lessons — stacking semantics are undefined.
**Fix:** Cast to `float` (and make the column decimal), and define stacking behavior (reject, queue, or extend).
### ~~2.3 Streak freeze logic covers arbitrarily long gaps~~
**Status: ✅ DONE.** A freeze now covers exactly one missed day. `processVictory` computes `daysMissed = daysSince - 1`; the streak is preserved **only** when `streak_freezes >= daysMissed`, in which case it spends one freeze per missed day (`streak_freezes -= daysMissed`) and lets today extend the streak by one (frozen days don't add to it). If freezes are insufficient the streak resets to 1 and freezes are left untouched. The rested-XP catch-up (`+200` on a 3+ day gap) now fires **only** when the streak actually lapsed — a freeze-covered gap no longer double-compensates. Covered by `tests/Unit/ProgressionServiceTest.php` (one freeze can't cover a multi-day gap; one-freeze-per-missed-day consumption; no rested XP when freezes cover a 3+ day gap), and the existing 2-day-gap tests still pass.

**Changes made:**
- `app/Services/ProgressionService.php` — rewrote the gap branch in the streak block: consume one freeze per missed day, preserve+extend only when fully covered, otherwise reset to 1; gate the `rested_xp_balance += 200` behind the insufficient-freezes (actual lapse) path.
- `tests/Unit/ProgressionServiceTest.php` — +3 tests for the new freeze/rested semantics.
- Verified: Pint clean; `ProgressionServiceTest` 27/27.

**File:** `app/Services/ProgressionService.php:57-66`
One streak freeze preserves (and even **increments**) the streak after any gap — 2 days or 60 days. Conventionally a freeze covers one missed day. Also, after a freeze-covered multi-day gap the user still collects the rested-XP bonus, double-compensating the absence.
**Fix:** Consume one freeze per missed day (or only allow a freeze for a 2-day gap), and don't increment the streak for the frozen day(s).
### ~~2.4 Equip endpoint accepts non-cosmetic items~~
**Status: ✅ DONE.** `StoreController::equip` now `abort_unless(in_array($item->type, ['title', 'avatar'], true), 422)` before writing the preference — matching `unequip`'s whitelist — so consumables (`streak_freeze`, `xp_boost`) can no longer create junk `equipped_*` keys. Covered by `tests/Feature/StoreTest.php` ("rejects equipping a non-cosmetic item and writes no junk preference").

**Changes made:**
- `app/Http/Controllers/StoreController.php` — added the cosmetic-type whitelist guard in `equip`.
- `tests/Feature/StoreTest.php` — +1 test (equip `streak_freeze` → 422, no junk pref).
- Verified: Pint clean; `StoreTest` 19/19.

**File:** `app/Http/Controllers/StoreController.php:132-144`
`equip` writes `preferences['equipped_'.$item->type]` for **any** owned item — equipping a `streak_freeze` creates a junk `equipped_streak_freeze` key. `unequip` correctly whitelists `['title', 'avatar']`; `equip` should too.
### ~~2.5 Side-effectful Inertia shared prop~~
**Status: ✅ DONE.** The `achievements_unlocked` shared prop is now **read-only** — `fn () => $request->user()?->pending_achievements ?? []` — and no longer mutates the user during prop resolution. Clearing happens through a new explicit endpoint `POST /achievements/acknowledge` (`AchievementController::acknowledge`, auth-only, `student.achievements.acknowledge`) that the `AchievementToast` component calls once it has consumed the toasts (`router.post(..., { preserveScroll, preserveState })`). The component dedups by id (`SvelteSet`) so the still-pending prop doesn't re-queue between display and acknowledgement. Covered by `tests/Feature/AchievementAcknowledgeTest.php` (page load does not clear; endpoint clears; endpoint requires auth).

**Changes made:**
- `app/Http/Middleware/HandleInertiaRequests.php` — `achievements_unlocked` closure now reads `pending_achievements` without the `update(... => null)` side effect.
- `app/Http/Controllers/AchievementController.php` (new) — `acknowledge()` nulls `pending_achievements` for the auth user.
- `routes/web.php` — `POST /achievements/acknowledge` in the auth/`student.` group (+ import).
- `resources/js/components/AchievementToast.svelte` — calls the acknowledge endpoint after queuing; `SvelteSet` id-dedup to avoid re-queuing.
- `tests/Feature/AchievementAcknowledgeTest.php` (new) — +3 tests.
- Verified: Pint clean; ESLint clean on the toast; full suite shows no new failures (lone failure = pre-existing `users.name` UNIQUE from concurrent 1.3 work).

**File:** `app/Http/Middleware/HandleInertiaRequests.php:60-66`
The `achievements_unlocked` shared prop **mutates the user** (`$user->update(['pending_achievements' => null])`) during prop resolution. Any Inertia response — including partial reloads or requests from pages that never render the toast — consumes and permanently discards pending achievement notifications.
**Fix:** Clear the pending list via an explicit acknowledgement endpoint the toast component calls, or only clear when the prop is actually requested by a full page that displays it.
### ~~2.6 SQLite-only SQL in achievement evaluation~~
**Status: ✅ DONE.** `resolveSpecificBlockTypeCompleted` no longer uses the SQLite-only `whereRaw("json_extract(... '$[' || block_index || '].type')")`. It now loads the user's `block_submissions` and the referenced lessons (`blocks` already cast to `array`) and resolves each submission's block type in PHP, tallying per type — driver-agnostic (works on MySQL/Postgres). Duplicate-unlock concern: the `achievement_user` pivot already has a composite primary key `(user_id, achievement_id)` so duplicates are blocked at the DB level; the `attach()` call (which would *throw* on a concurrent re-insert) is now `syncWithoutDetaching([$id => ['unlocked_at' => now()]])`, making the unlock idempotent. Covered by `tests/Feature/EvaluateAchievementsTest.php` (block-type counting from lesson JSON; idempotent double-evaluation; simple `total_xp_earned` unlock).

**Changes made:**
- `app/Listeners/EvaluateAchievements.php` — rewrote `resolveSpecificBlockTypeCompleted` to a PHP-side tally (removed the `join` + `whereRaw`/`json_extract`/`||`); changed `attach` → `syncWithoutDetaching` in `handle`.
- `tests/Feature/EvaluateAchievementsTest.php` (new) — +3 tests (call the listener directly to avoid the `ShouldQueueAfterCommit` + `RefreshDatabase` transaction interplay).
- Verified: Pint clean; `EvaluateAchievementsTest` 3/3; no `whereRaw`/`json_extract`/`||` left in the listener; full suite no new failures (lone failure = pre-existing `users.name` UNIQUE from concurrent 1.3 work).

**File:** `app/Listeners/EvaluateAchievements.php:135-138`
```php
->whereRaw("json_extract(lessons.blocks, '$[' || block_submissions.block_index || '].type') = ?", ...)
```
`||` string concatenation and this `json_extract` path style are SQLite-specific; this breaks on MySQL (`CONCAT`, `JSON_EXTRACT` with `JSON_UNQUOTE`). Since the rest of the app is driver-agnostic, this is a deployment landmine.
Also: `attach()` at line 43 has no guard against a concurrently-queued duplicate evaluation — the `achievement_user` pivot needs a unique index on `(user_id, achievement_id)` (verify it exists), otherwise duplicate unlocks are possible.
### ~~2.7 All-time leaderboard exists only in Redis~~
**Status: ✅ DONE.** New artisan command `app:rebuild-leaderboard` (`app/Console/Commands/RebuildLeaderboard.php`) repopulates `leaderboard:all_time` from the durable source of truth: it `Redis::del`s the set, then `chunkById`s `users` where `is_shadowbanned = false` and `xp > 0` and `zadd`s `xp` keyed by `user->id` (matching the `ProgressionService`/`LeaderboardSeeder` member convention). Run it on deploy or after any Redis flush/eviction to recover everyone's rank. Weekly is intentionally **not** rebuilt — there is no per-week XP column in the DB, so `leaderboard:weekly` only re-accrues going forward. Covered by `tests/Feature/RebuildLeaderboardTest.php` (zero-xp and shadowbanned users excluded; correct member/score per user). **Deploy recommendation (infra, not code):** configure the Redis instance with `maxmemory-policy noeviction` + persistence (RDB/AOF) so the sets aren't silently evicted.

**Changes made:**
- `app/Console/Commands/RebuildLeaderboard.php` (new) — `app:rebuild-leaderboard`: `del` + chunked `zadd` from `users.xp` (excludes shadowbanned and `xp = 0`); reports rebuilt count.
- `tests/Feature/RebuildLeaderboardTest.php` (new) — +1 test (Mockery `Redis` expectations proving exclusions + correct args).
- Verified: Pint clean; command auto-discovered (`php artisan list`); `RebuildLeaderboardTest` 1/1; full suite no new failures (lone failure = pre-existing `users.name` UNIQUE from concurrent 1.3 work).

**Files:** `app/Services/ProgressionService.php:134`, `app/Console/Commands/ResetWeeklyLeaderboard.php`
`leaderboard:all_time` is write-only Redis state. A Redis flush/eviction/migration silently zeroes everyone's all-time score, with no rebuild path — even though `users.xp` in the DB is the source of truth.
**Fix:** Add an `app:rebuild-leaderboard` command that repopulates the sorted set from `users` (`xp`, excluding shadowbanned). Configure the Redis instance with `noeviction` + persistence.

---
## 3. Performance Issues

> **Fix audit (2026-06-15):** 3.1 ✅ DONE · 3.2 ✅ DONE · 3.3 ✅ DONE · 3.4 ✅ DONE · 3.5 ✅ DONE · 3.6 ✅ DONE
### ~~3.1 `CourseController::show` ships every lesson's full `blocks` JSON~~
**Status: ✅ DONE.** The `lessons` eager load now `->select(['id','course_id','name','slug','xp_reward','coin_reward','estimated_duration','is_boss','sort_order'])` — the heavy `blocks` JSON (and all its answer keys) is no longer serialized to the course page. `id`/`course_id` kept for relation hydration + the resume/completed lookups, `sort_order` for ordering. Covered by `tests/Feature/CourseDetailTest.php` (lessons prop carries metadata, `missing('lessons.0.blocks')`).
**File:** `app/Http/Controllers/CourseController.php:51-57`
The course page only needs lesson metadata (name, slug, xp, duration, boss flag), but `'lessons' => $course->lessons` serializes the complete `blocks` array for every lesson — easily hundreds of KB per page, and it leaks all answer keys (see 1.2) for every lesson in the course at once.
**Fix:** `$query->select(['id','course_id','name','slug','xp_reward','coin_reward','estimated_duration','is_boss','sort_order'])` on the eager load, or a dedicated lesson-summary resource.
### ~~3.2 `WorldController::index` loads all courses for all worlds~~
**Status: ✅ DONE.** `WorldController::index` now eager-loads `courses` with `->published()->select(['id','world_id','name','slug','description','min_level_requirement'])` — only the columns `CourseResource` needs, and only published courses. Same `published()` constraint applies on `show`.
**File:** `app/Http/Controllers/WorldController.php:16`
`World::with('themePack', 'courses')->get()` pulls every course row (full columns) for the world map. Select only the columns `WorldResource` needs, and constrain to published courses.
### ~~3.3 Achievement evaluation re-runs every metric on every block claim~~
**Status: ✅ DONE.** `ProgressRegistered` now carries a `?string $source` (`LessonController` dispatches `'lesson'` / `'block'`), and `EvaluateAchievements::handle` filters the grouped metric set to only those a given source can affect (`$grouped->filter(...)` on the metric-type key — note `EloquentCollection::only()` would have called `getKey`, so `filter` is used). `resolveSpecificCourseCompleted` replaced the per-achievement `count`+`pluck` pair with two portable grouped `selectRaw(... COUNT(*) ... groupBy('course_id'))` queries for all target courses at once. Covered by `tests/Feature/EvaluateAchievementsTest.php` (6/6: block-source skips lesson/course metrics, lesson-source skips block-type metrics, multi-course unlock in one pass, idempotency, etc.).
**File:** `app/Listeners/EvaluateAchievements.php`
Each block/lesson claim queues a full evaluation: loads all unearned achievements, then for course-completion achievements runs **two queries per achievement** (`count` + `pluck`) and a JSON-extract join per block-type achievement. Fine at current scale, but it grows linearly with achievement count × submission rate.
**Fixes:** cache total-lesson counts per course; combine the `count`/`pluck` pair into one query; consider only evaluating metric types relevant to the event (e.g. block claims can't change `specific_course_completed` unless a lesson was submitted).
### ~~3.4 Missing indexes for common lookups~~
**Status: ✅ DONE.** Added single-column `lesson_id` indexes to `block_submissions` and `lesson_submissions` (migration `2026_06_15_084646_add_lookup_indexes_to_submissions`) — the existing `unique(user_id, lesson_id[, block_index])` indexes can't serve lesson-side lookups (`BlockSubmissionsRelationManager`, `LessonFunnelWidget` correlated subqueries) since `lesson_id` isn't the leftmost column, and SQLite doesn't auto-index FK columns. `activity_log` growth fixed at the root: `User::getActivitylogOptions` switched from `logFillable()` to `logOnly(['name','forename','lastname','birthday','gender','email','role'])` so high-churn gamification columns (xp/coins/streak/boosts) are no longer logged on every victory, and `activitylog:clean` is now scheduled daily (retention `clean_after_days => 365` already set). Covered by `tests/Feature/SchemaIndexTest.php` (both indexes present) + `tests/Feature/ActivityLogScopeTest.php` (xp/coin change not logged, name change logged). **Stale sub-items (evaluated, no-op post-1.3/1.4):** `lesson_submissions(user_id, course_id)` — no query filters by that pair (all use `user_id`+`lesson_id`, covered by the unique index); `users.name` index — 1.3 removed `whereIn('name')`, only `SearchController`'s leading-wildcard LIKE remains (no index helps; see 3.5).

**Original finding:**
- `lesson_submissions(user_id, course_id)` — `CourseController` filters by user + lesson set on every course page (the unique `(user_id, lesson_id)` index covers user-prefix lookups, but queries filtering by slug list still scan).
- `block_submissions.lesson_id` gets an index via FK in SQLite? No — SQLite does not auto-index FK columns; `LessonController::show` queries `(user_id, lesson_id)` which the unique index covers, but `BlockSubmissionsRelationManager` style lesson-side queries need `lesson_id` first.
- `users.name` — `LeaderboardController` does `whereIn('name', ...)` on every leaderboard view; unindexed.
- `activity_log` grows unboundedly (`HasActivity` on `User` logs every fillable change — including every XP/coin update from every block claim). Consider pruning (`activitylog:clean`) and logging only meaningful events; this table will become the largest in the DB by far.
### ~~3.5 `SearchController` leading-wildcard LIKE queries~~
**Status: ✅ DONE.** User input is now escaped before the `LIKE` (`'%'.addcslashes((string) $query, '%_\\').'%'`) so `%`/`_` can't be injected as wildcards, and the `/search` route carries `->middleware('throttle:60,1')`. The leading-wildcard `LIKE` itself is intentionally kept (acceptable at current scale; FTS5/`whereFullText` deferred until content grows).
**File:** `app/Http/Controllers/SearchController.php`
`LIKE '%term%'` on name + description can't use indexes. Acceptable at small scale; if content grows, move to SQLite FTS5 / `whereFullText`. Also escape `%`/`_` in the user query (`addcslashes($query, '%_\\')`) so wildcards can't be injected, and add `throttle` middleware — it's an unauthenticated-cost query endpoint hit on every keystroke.
### ~~3.6 Leaderboard page issues two extra Redis round-trips per request~~
**Status: ✅ DONE.** `LeaderboardController::index` now batches all three reads into one `Redis::pipeline` — `zrevrange` (top 50), `zrevrank` (player rank), `zscore` (player score) — destructured into `[$rawEntries, $userRank, $userScore]`, one round-trip instead of three.
**File:** `app/Http/Controllers/LeaderboardController.php:57-58`
`zrevrank` + `zscore` can be pipelined with the `zrevrange` call. Minor, but free.

---
## 4. Improvements & Code Quality

> **Fix audit (2026-06-16):** 4.1 ✅ DONE (storage-URL consistent; equipped-item hydration extracted to `App\Concerns\ResolvesEquippedItems`, used by `LeaderboardController`/`ProfileController`/`PublicProfileController`; achievement-list mapping extracted to `App\Concerns\BuildsAchievementList`, used by `ProfileController`/`PublicProfileController`; `LessonController` constructor already used promotion) · 4.2 ✅ DONE (scope param validated; `StoreItem.type`/`purchase_type` now backed enums) · 4.3 ❌ TODO (weekly rank still `null` when no weekly score) · 4.4 ✅ DONE (`WorldController::index` now calls `->ordered()` (SortableTrait scope) so the world map is sorted by `sort_order`; courses/lessons were already ordered via relations) · 4.5 ❌ TODO (certificate PDF still rendered inline, no cache/throttle) · 4.6 ✅ DONE (critical-path tests now exist: block-index bounds, double-submit race, store purchase race, level gating, unpublished visibility)
### ~~4.1 Consistency~~
**Status: ✅ DONE.**
- **Storage URL generation:** all three controllers now use `Storage::disk('public')->url(...)` (via the shared concern below).
- **Equipped-item hydration** extracted to `App\Concerns\ResolvesEquippedItems` (`equippedItemIds`/`fetchEquippedItems`/`resolveEquipped`/`buildEquipped`). `LeaderboardController` uses the batch path (`equippedItemIds` + `fetchEquippedItems` + `buildEquipped` across many users); `ProfileController`/`PublicProfileController` use the single-user `resolveEquipped`.
- **Achievement list mapping** extracted to `App\Concerns\BuildsAchievementList::buildAchievementList`, used by `ProfileController` + `PublicProfileController`.
- **Constructor style:** `LessonController` already uses promotion — finding was stale.

**Files:** `app/Concerns/ResolvesEquippedItems.php` (new), `app/Concerns/BuildsAchievementList.php` (new), `app/Http/Controllers/LeaderboardController.php`, `app/Http/Controllers/ProfileController.php`, `app/Http/Controllers/PublicProfileController.php`. Covered by existing `Leaderboard`/`Profile` feature tests (full suite green: 204 passed).
### ~~4.2 Validation gaps~~
**Status: ✅ DONE.**
- **`scope` param:** `LeaderboardController::index` now validates `'scope' => ['sometimes', 'in:weekly,all_time']` (absent still defaults to `weekly`; bad value → 422).
- **StoreItem enums:** new string-backed `App\Enums\StoreItemType` (`title`/`avatar`/`streak_freeze`/`xp_boost`) + `App\Enums\PurchaseType` (`permanent`/`one_time`/`consumable`), both implementing Filament `HasLabel`+`HasColor`. Cast on `StoreItem` (`type`/`purchase_type`). `StoreController` compares enum cases instead of string literals; `equip` writes `'equipped_'.$item->type->value` (attribute is now an enum instance). `StoreItemForm`/`StoreItemsTable` source options + badge label/color from the enums (removed the duplicated `TYPES`/`PURCHASE_TYPES`/`CONSUMABLE_TYPES` constants and hand-written `match` blocks). Frontend unaffected — backed enums serialize to plain strings in `toArray()`.
- **Preferences split:** deferred — `updateSettings` validation is already correct; separating settings vs. equipped-item ids into distinct columns is an invasive migration for low value.

**Files:** `app/Enums/StoreItemType.php` (new), `app/Enums/PurchaseType.php` (new), `app/Models/StoreItem.php`, `app/Http/Controllers/LeaderboardController.php`, `app/Http/Controllers/StoreController.php`, `app/Filament/Resources/StoreItems/Schemas/StoreItemForm.php`, `app/Filament/Resources/StoreItems/Tables/StoreItemsTable.php`. Tests: `tests/Feature/LeaderboardTest.php` (invalid scope → 422), `tests/Feature/StoreTest.php` (enum cast + string serialization). Verified: Pint clean; full suite 205 passed / 9 skipped / 0 failed. **Note (out of scope):** the `type` migration enum declares `avatar_frame` while the app uses `avatar` — pre-existing mismatch, tolerated on SQLite, would break on MySQL; fix in a separate migration.
### 4.3 Weekly leaderboard rank fallback
**File:** `app/Http/Controllers/LeaderboardController.php:60-66`
When the player has no weekly score, `rank` is `null` for weekly scope but computed from DB for all-time. The asymmetry produces a "—" rank for any student who hasn't played this week; consider showing total-player count or "unranked" explicitly in the UI contract.
### ~~4.4 `World`/`Course` ordering~~
**Status: ✅ DONE.** `WorldController::index` now chains `->ordered()` (the `SortableTrait` scope on `World`, `order_column_name => 'sort_order'`) so the world map renders in `sort_order` instead of insertion-order luck. The `courses` eager load (`World::courses()`) and `CourseController`'s lessons were already `orderBy('sort_order')` — no change needed there; `show()` operates on a single world. Covered by `tests/Feature/WorldPublishVisibilityTest.php` ("orders the world map by sort_order").
**File:** `app/Http/Controllers/WorldController.php:16`
`WorldController::index` and the `courses` eager load don't `orderBy('sort_order')` even though Spatie Sortable is installed and `CourseController` orders lessons. World map ordering is currently insertion-order luck. (If `World`/`Course` use `SortableTrait` with a default scope this is moot — verify.)
### 4.5 Certificate route does heavy PDF work inline
`WorldController::certificate` renders a DomPDF document per request with no caching or throttle. Cheap to abuse; consider caching the generated PDF per (user, world) or rate-limiting the route.
### 4.6 Tests
There are feature tests in the repo, but the critical paths found above (block-index bounds, double-submit race, store purchase race, level gating on `show`/block claim) are exactly the ones tests don't appear to cover. Suggested additions:
- claiming an out-of-range block index returns 4xx and awards nothing,
- claiming the same block/lesson twice concurrently awards XP once,
- purchase with insufficient coins / sold-out stock inside concurrent requests,
- below-level user cannot view or claim blocks on a gated lesson,
- unpublished world/course invisible on student surfaces.

---
## 5. Summary — Priority Order

> **Status (2026-06-15):** §1 (1.1–1.9) ✅ all DONE · §2 (2.1–2.7) ✅ all DONE · §3 (3.1–3.6) ✅ all DONE · §4 remaining: 4.3, 4.5 TODO (low) and 4.4 partial; 4.1, 4.2 ✅ DONE. Every Critical/High/Medium item in the table below is resolved.

| #   | Issue                                                    | Severity | Effort |
| --- | -------------------------------------------------------- | -------- | ------ |
| 1.1 | Block-index XP farming                                   | Critical | Small  |
| 1.2 | Answers shipped to client, no server validation          | Critical | Medium |
| 1.8 | Unthrottled student login                                | Critical | Small  |
| 1.3 | Non-unique `name` as identity key (leaderboard/profiles) | High     | Medium |
| 1.4 | Slug-vs-id `lesson_id` inconsistency                     | High     | Medium |
| 1.5 | Double-reward race on submits                            | High     | Small  |
| 1.6 | Missing level gate on lesson show / block claim          | High     | Small  |
| 1.7 | Unpublished content visible                              | High     | Small  |
| 1.9 | Incomplete admin progress reset (incl. Redis)            | High     | Small  |
| 2.1 | Store purchase races                                     | High     | Small  |
| 2.2 | XP boost multiplier int-truncation                       | Medium   | Small  |
| 2.5 | Side-effectful shared prop drops achievements            | Medium   | Small  |
| 2.6 | SQLite-only SQL in achievements                          | Medium   | Small  |
| 2.7 | Redis-only all-time leaderboard (no rebuild)             | Medium   | Small  |
| 3.1 | Course page ships all blocks JSON                        | Medium   | Small  |
| 3.4 | Missing indexes + unbounded activity log                 | Medium   | Small  |
| 2.3 | Streak freeze covers any gap                             | Low      | Small  |
| 2.4 | Equip accepts consumables                                | Low      | Small  |
| 3.5 | Search LIKE escaping + throttle                          | Low      | Small  |
| 4.x | Code-quality consolidation                               | Low      | Medium |

---
## 6. New Issues

**Audit date:** 2026-06-15
**Scope:** Re-sweep of controllers, policies, services, listeners, models, and routes following the 1.x–3.x fixes. These are findings **not** covered by the sections above.

### 6.1 `submitClaim` bypasses the publish gate (rewards on unpublished courses)
**File:** `app/Http/Controllers/LessonController.php:72`
After fix 1.6, `show` and `submitBlockClaim` both call `$this->authorize('view', $course)` (role + `is_published` + level via `CoursePolicy`). But `submitClaim` (the **lesson-completion** endpoint) was never converted — it still does only the old manual level check:

```php
if ($user->level < $lesson->course->min_level_requirement) { ... }
```

It never checks `is_published`. A student who knows (or guesses) the slug of a lesson in a **draft / unpublished** course can `POST /lessons/{slug}/submit` and collect the full lesson `xp_reward` + `coin_reward` (and trigger world-completion bonuses), even though the course is hidden everywhere else. This re-opens 1.7 on the highest-value reward path.
**Fix:** Replace the manual level check in `submitClaim` with `$this->authorize('view', $lesson->course)`, matching `show`/`submitBlockClaim`. Covers role, publish, and level in one place.
**Severity:** High · **Effort:** Small

### 6.2 `CoursePolicy::view` ignores the parent world's `is_published`
**File:** `app/Policies/CoursePolicy.php:22-40`
The policy checks `$course->is_published` but never the owning `World`. A **published course inside an unpublished world** is therefore reachable by slug on `CourseController::show`, `LessonController::show`, and `submitBlockClaim` — `WorldController` gates the world surfaces (1.7), but the course/lesson routes bind directly and only consult the course flag. Draft worlds leak their published courses.
**Fix:** In `CoursePolicy::view`, also deny when `! $course->world?->is_published` (eager-load `world` where needed). Consider a single `isAccessible` helper so world+course publish + level live in one place.
**Severity:** High · **Effort:** Small

### 6.3 Course `prerequisite_course_id` is configured but never enforced
**Files:** `app/Models/Course.php:42-45`, `app/Policies/CoursePolicy.php`, `app/Filament/Resources/Courses/Schemas/CourseForm.php:71-74`
Admins can set a course prerequisite (`prerequisite` relation, exposed in `CourseForm`), but **nothing in the student flow checks it**. `CoursePolicy::view` gates only role/publish/level. A student at the required level can open and complete an advanced course while skipping the course it depends on — the prerequisite is dead configuration.
**Fix:** In `CoursePolicy::view` (or a dedicated check), deny when the user has not completed `prerequisite_course_id` (e.g. all of the prerequisite's lessons present in `lesson_submissions`). Surface a clear "Finish {prerequisite} first" message. Decide whether this also hides the course on `WorldDetail`.
**Severity:** Medium · **Effort:** Medium

### 6.4 `activateItem` consumes inventory for items it can't activate
**File:** `app/Http/Controllers/StoreController.php:116-146`
`activateItem` only has branches for `streak_freeze` and `xp_boost`. For any **other** owned item (a `title`/`avatar` cosmetic, or an unknown type) both branches are skipped, `$user->save()` is a no-op — and then the method still falls through to **delete or decrement the inventory row**:

```php
if ($inventory->quantity <= 1) { $inventory->delete(); }
else { $inventory->decrement('quantity'); }
```

So a student who activates a cosmetic (or any non-consumable) destroys it for zero effect — silent loss of a purchased item. `equip` got a type whitelist in 2.4; `activate` never did.
**Fix:** Guard the activatable types up front — `abort_unless(in_array($item->type, ['streak_freeze', 'xp_boost'], true), 422)` — before any consumption, mirroring `equip`/`unequip`.
**Severity:** Medium · **Effort:** Small

### 6.5 Redis leaderboard increment isn't rolled back with the surrounding DB transaction
**Files:** `app/Services/ProgressionService.php:141-144`, `app/Http/Controllers/LessonController.php:117-130, 229-242`
`processVictory` runs its body in `DB::transaction(...)` and, inside it, issues `Redis::zincrby('leaderboard:all_time' / ':weekly', ...)`. Both callers wrap `processVictory` in an **outer** `DB::transaction` and then write the submission row:

```php
DB::transaction(function () {
    $result = $this->progressionService->processVictory(...); // nested savepoint + Redis writes
    $submission->update([...]);                               // if this throws ↓
});
```

Redis is not part of the DB transaction. If the outer transaction (or the `submission->update`) fails and rolls back, the user's XP is reverted in the DB **but the Redis score increment stays** — leaderboard drifts above true XP. The same applies to any deadlock-driven transaction retry (the Redis write would double-count).
**Fix:** Move the `Redis::zincrby` calls out of the transaction — perform them after the outermost commit (e.g. `DB::afterCommit(...)` or return the delta and increment in the controller post-commit). The 2.7 `app:rebuild-leaderboard` command remains the reconciliation backstop.
**Severity:** Medium · **Effort:** Small

### 6.6 World-completion bonus XP doesn't re-evaluate achievements
**File:** `app/Listeners/HandleWorldCompletion.php:31`
`HandleWorldCompletion` awards a 500 XP / 250 coin bonus via `processVictory`, but never dispatches `ProgressRegistered`. So the bonus can push a user across an XP/level/`total_coins_earned` achievement threshold and it **won't unlock until the next ordinary claim**. The flow only fires `ProgressRegistered` from `LessonController`, not from the world-bonus path.
**Fix:** Dispatch `ProgressRegistered::dispatch($user, 'lesson')` after the bonus in `HandleWorldCompletion`, or have `processVictory` callers consistently raise the event.
**Severity:** Low · **Effort:** Small

### 6.7 `acknowledge` clears all pending achievements unconditionally (lost-toast window)
**File:** `app/Http/Controllers/AchievementController.php:15-20`
`acknowledge` does `update(['pending_achievements' => null])` — it wipes the whole list, not the ids the client actually displayed. `EvaluateAchievements` is `ShouldQueueAfterCommit`; if it appends a new achievement between the client rendering its toasts and POSTing the acknowledgement, that new achievement is nulled out before it's ever shown. The 2.5 client-side `SvelteSet` dedup doesn't help — the data is gone server-side.
**Fix:** Have the client send the acknowledged ids and remove only those (`pending = array_values(array_diff_key(...))`), or re-read and diff inside a row lock.
**Severity:** Low · **Effort:** Small

### 6.8 Student-only routes have no `role` gate (admins fall through)
**File:** `routes/web.php:32`
The authenticated group applies only `auth` — no role check. `CoursePolicy` blocks admins from course/lesson views, but `/store`, `/leaderboard`, `/profile`, `/search`, and the inventory endpoints have no such guard, so an `admin` session can hit student-only surfaces (and a leaderboard/profile is built for an account that never plays). Low risk (admins are trusted), but it's an inconsistent trust boundary.
**Fix:** Add a `role:student` (or `student`-only) middleware to the group, and route admins to Filament. Keep any genuinely shared routes explicitly outside it.
**Severity:** Low · **Effort:** Small
