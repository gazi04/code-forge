---
name: codeforge-development
description: "Activate when building educational content, gamification features, or student-facing UI for this app — a gamified CS learning platform for kids aged 7–16. Triggers when working on: worlds, courses, lessons, or block types (quiz, code challenge, labyrinth, sequence, bughunt, variable matching, text content); designing age-appropriate difficulty or vocabulary; adding XP/coins/level/streak mechanics; building block Filament schemas; creating theme packs; or any work targeting the student learning experience. Also activates for questions about content structure, pedagogical sequencing, or what block type to use for a given teaching objective."
license: MIT
metadata:
  author: gazi04
---

# CodeForge Development

CodeForge (Arcane.dev) teaches kids aged 7–16 computer science, programming, logical thinking, and algorithms through a gamified world-progression experience. Every feature decision must balance **educational clarity** with **engagement**. When in doubt, choose the simpler, more playful path.

## App Hierarchy

```
World  (themed environment, e.g. "The Crystal Caverns")
  └── Course  (topic, e.g. "Variables & Data Types")
        └── Lesson  (one learning unit, e.g. "What is a Variable?")
              └── Blocks[]  (interactive content cells inside the lesson)
```

- A **World** owns a `ThemePack` that skins the entire UI for its courses.
- A **Course** belongs to a World and has a `min_level_requirement`.
- A **Lesson** stores its content as `blocks` — a JSON array of typed block objects.
- A **Block** is an interactive unit. Students must complete `is_required` blocks before advancing.

## Age Tiers

Design all content relative to these tiers. Always ask which tier is in scope before writing lesson text, difficulty, or code examples.

| Tier | Ages | Language | Code concepts | Block types favoured |
|---|---|---|---|---|
| **Novice** | 7–10 | Very simple. Short sentences. Spell out every step. No jargon — use analogies (variables = labelled boxes). | Sequences, simple loops, if/else with emoji | Labyrinth, Sequence, Quiz (single), TextBlock |
| **Apprentice** | 11–13 | Plain language. Introduce terms with definitions. Light storytelling framing. | Variables, loops, functions, basic data types | All blocks. Code challenges with Python 3 preferred |
| **Adept** | 14–16 | Technical vocabulary is OK. Can handle multi-step reasoning. | Algorithms, data structures, complexity concepts, debugging | BugHunt, Code Challenge, VariableMatching with abstract pairs |

---

## Block Types & Data Schemas

All blocks are stored in `lessons.blocks` as a JSON array. Each item has `{ "type": "<type>", "data": { ... } }`.

### `text_content`
Narrative or instructional text. Use for introducing concepts before interactive blocks.
```json
{
  "type": "text_content",
  "data": {
    "content": "Markdown string. Supports **bold**, `code`, and lists."
  }
}
```
**Guidance:** Open every lesson with a `text_content` block. Keep it under 150 words for Novice tier, 250 for Adept. Use metaphors that match the world theme.

---

### `quiz`
Multiple or single choice question. Use to test recall or concept understanding.
```json
{
  "type": "quiz",
  "data": {
    "question_type": "single | multiple",
    "question": "String — the question text",
    "is_required": true,
    "game_icon": "❓",
    "game_title": "String",
    "instructions": "String",
    "answers": [
      { "text": "String", "is_correct": true, "feedback": "Optional per-answer feedback" },
      { "text": "String", "is_correct": false, "feedback": null }
    ],
    "xp_reward": 50,
    "coin_reward": 10
  }
}
```
**Guidance:**
- Always have exactly one correct answer for `single` type.
- For `multiple` type, at least two correct answers.
- Always fill `feedback` on wrong answers for Novice/Apprentice tiers — kids need to understand why they were wrong.
- Keep distractors plausible, never trick with typos.

---

### `code_challenge`
In-browser code execution against test cases. Python via Pyodide; JS via native eval.
```json
{
  "type": "code_challenge",
  "data": {
    "language": "python | javascript",
    "description": "String — shown above the editor",
    "initial_code": "# starter code the student sees",
    "solution_code": "# kept secret, for reference only",
    "is_required": false,
    "game_icon": "🖥️",
    "game_title": "String",
    "instructions": "String",
    "test_cases": [
      {
        "name": "Should print Hello World",
        "setup_code": "# optional code injected AFTER student code (e.g. call their function)",
        "expected_output": "Hello World",
        "is_hidden": false
      }
    ],
    "xp_reward": 50,
    "coin_reward": 10
  }
}
```
**Guidance:**
- Always provide `initial_code` with scaffolding — never blank editor for Novice/Apprentice.
- `setup_code` runs after the student's code. Use it to call their function and capture output.
- Keep `expected_output` exact — trailing newlines matter.
- Mark 1–2 tests `is_hidden: true` for Adept tier to teach defensive coding.
- Prefer `print()` output over return values — output comparison is simpler to validate.

---

### `labyrinth_challenge`
Grid-based programming puzzle. Student builds a command queue (FORWARD, TURN_LEFT, TURN_RIGHT) to navigate a player from `S` to `E`.
```json
{
  "type": "labyrinth_challenge",
  "data": {
    "map_layout": "S . . #\n. # . #\n. . . E",
    "max_commands": 8,
    "is_required": false,
    "game_icon": "🏃",
    "game_title": "String",
    "instructions": "String",
    "xp_reward": 50,
    "coin_reward": 10
  }
}
```
**Map symbols:** `S` = start, `E` = end, `#` = wall, `.` = open path. Rows separated by `\n`, cells by spaces.

**Guidance:**
- Novice: 3×3 or 4×4 grid, no `max_commands` limit, straight path with one turn.
- Apprentice: 5×5 grid, optional limit, multiple turns.
- Adept: 6×6+, tight `max_commands` limit to enforce efficiency (teaching optimization).
- Always ensure exactly one valid shortest path exists.

---

### `sequence_challenge`
Student restores shuffled items to the correct order by swapping pairs. Teaches algorithmic thinking and ordering.
```json
{
  "type": "sequence_challenge",
  "data": {
    "correct_sequence": [
      { "value": "Step 1 text" },
      { "value": "Step 2 text" },
      { "value": "Step 3 text" }
    ],
    "is_required": false,
    "game_icon": "📜",
    "game_title": "String",
    "instructions": "String",
    "xp_reward": 50,
    "coin_reward": 10
  }
}
```
**Guidance:**
- Items are the correct order — the engine shuffles automatically.
- 3–4 items for Novice, up to 6 for Adept.
- Good uses: sorting algorithm steps, if/else decision steps, program execution order, recipe steps as an analogy.
- Make items distinct enough that a child can tell them apart at a glance.

---

### `bughunt_challenge`
Student clicks buggy code lines and picks the correct fix from 3 options (correct + 2 decoys). Teaches debugging.
```json
{
  "type": "bughunt_challenge",
  "data": {
    "code_lines": [
      { "type": "clean", "displayed_text": "def greet(name):" },
      {
        "type": "buggy",
        "displayed_text": "    print('Hello ' + name",
        "correct_text": "    print('Hello ' + name)",
        "decoy_1": "    print('Hello', name,)",
        "decoy_2": "    Print('Hello ' + name)"
      },
      { "type": "clean", "displayed_text": "greet('World')" }
    ],
    "is_required": false,
    "game_icon": "🐛",
    "game_title": "String",
    "instructions": "String",
    "xp_reward": 50,
    "coin_reward": 10
  }
}
```
**Guidance:**
- 1 bug for Novice, 2–3 for Apprentice, 3–4 for Adept.
- Decoys must be plausible wrong answers — not random. Each decoy should represent a real misconception.
- Common good bug types: missing parenthesis, wrong operator (`=` vs `==`), capitalisation (`Print`), off-by-one, wrong indent.
- Total code block: 5–10 lines max. Longer blocks are overwhelming.

---

### `variable_matching_challenge`
Student connects left-column items to their right-column matches. Teaches vocabulary, concept mapping, and data type associations.
```json
{
  "type": "variable_matching_challenge",
  "data": {
    "pairs": [
      { "left_item": "int", "right_item": "Whole number" },
      { "left_item": "str", "right_item": "Text" },
      { "left_item": "bool", "right_item": "True or False" }
    ],
    "is_required": false,
    "game_icon": "🔗",
    "game_title": "String",
    "instructions": "String",
    "xp_reward": 50,
    "coin_reward": 10
  }
}
```
**Guidance:**
- 3–4 pairs for Novice, up to 6 for Adept.
- Both columns are scrambled — pairs must be uniquely matchable without context.
- Good uses: data types ↔ examples, concept ↔ definition, code symbol ↔ meaning, algorithm ↔ use case.

---

## Gamification Mechanics

| Mechanic | Rule |
|---|---|
| **XP** | Block default: 50 XP. Lesson completion: set on the lesson itself. Scale by difficulty — Labyrinth/CodeChallenge can go to 100–150. |
| **Coins** | Block default: 10. Lesson default: varies. Used as in-app currency. |
| **Level** | Calculated by `ProgressionService`. Every level-up triggers confetti modal via `flash.game_result.leveled_up`. |
| **Streak** | Incremented by `ProgressionService` on daily activity. Shown in nav. |
| **`is_required`** | When `true` on a block, student cannot submit the lesson until that block is cleared. Use for core concept validation only — not every block. |
| **`is_boss`** | Lesson flag. Boss lessons should be harder and grant more XP/coins. Not yet rendered differently — mark for future feature. |

---

## Lesson Structure Principles

A well-structured lesson for this platform:

1. **Hook** — `text_content` block: short world-themed story or problem setup (under 100 words for Novice).
2. **Teach** — `text_content` block: explain the concept with an analogy. One concept per lesson.
3. **Practice** — 1–2 interactive blocks. First block should be scaffolded (easier). Second can be harder.
4. **Validate** — 1 `quiz` or `bughunt` marked `is_required: true`. This is the gate.
5. **Optional challenge** — Bonus block (not required) for kids who want more.

**Anti-patterns to avoid:**
- Multiple unrelated concepts in one lesson.
- Opening with a quiz before teaching anything.
- Code challenges with blank starter code for Novice/Apprentice.
- More than 6 blocks per lesson — kids lose focus.
- `is_required: true` on more than 2 blocks per lesson.

---

## Theme Pack `config` Schema

Theme packs live on `World` → `ThemePack.config` (cast to array).

```json
{
  "palette": {
    "primary": "#8b5cf6",
    "secondary": "#0f172a",
    "accent": "#10b981",
    "background": "#09090b",
    "surface": "#18181b",
    "text": "#f8fafc"
  },
  "ui": {
    "font_style": "default | monospace | rounded | medieval | futuristic",
    "border_radius": "none | sm | md | lg | full",
    "card_style": "default | bordered | pixel | embossed | glassy"
  },
  "background": {
    "style": "solid | gradient | image | pattern",
    "value": "#09090b | linear-gradient(...) | https://url | https://pattern-url"
  },
  "audio": {
    "background_music_url": "https://url-to-mp3-or-null"
  }
}
```

All block components use `var(--primary-color)`, `var(--bg-color)`, `var(--surface-color)`, `var(--text-color)`, `var(--accent-color)` — injected by `StudentLayout.svelte` from the theme config. Never hardcode hex values in block components.

---

## Common Pitfalls

- Adding `is_required: true` to `text_content` blocks — text blocks have no completion state.
- Creating a `labyrinth_challenge` where no valid path exists from `S` to `E`.
- Writing `expected_output` with a trailing newline when the student's `print()` won't produce one.
- Setting `max_commands` lower than the minimum moves required to solve the labyrinth.
- Using programming jargon in Novice-tier quiz questions without defining terms first.
- Building a `bughunt_challenge` where all lines look the same — kids need visual distinction to debug.
