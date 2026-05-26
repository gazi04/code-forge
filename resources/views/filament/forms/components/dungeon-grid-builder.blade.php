<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <style>
        .dungeon-builder {
            background-color: #111827;
            border: 1px solid #374151;
            border-radius: 0.75rem;
            padding: 1.25rem;
            color: #e5e7eb;
        }
        .dungeon-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            align-items: center;
            background-color: #030712;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid #1f2937;
            margin-bottom: 1.25rem;
        }
        .dungeon-input {
            width: 4rem;
            background-color: #111827;
            border: 1px solid #374151;
            color: #ffffff;
            border-radius: 0.375rem;
            padding: 0.25rem;
            text-align: center;
            font-weight: bold;
        }
        .dungeon-input:focus {
            outline: none;
            border-color: #3b82f6;
        }
        .dungeon-palette {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .dungeon-brush {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid #374151;
            background-color: #1f2937;
            color: #d1d5db;
            cursor: pointer;
            transition: all 0.2s;
            font-family: monospace;
            font-size: 0.75rem;
            text-transform: uppercase;
            box-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        .dungeon-brush:hover {
            background-color: #374151;
        }
        .dungeon-brush.is-active {
            background-color: #3b82f6;
            border-color: #93c5fd;
            color: white;
            transform: scale(1.05);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.4);
        }
        .dungeon-canvas-wrapper {
            overflow: auto;
            background-color: #030712;
            padding: 1.5rem;
            border-radius: 0.5rem;
            border: 1px solid #1f2937;
            display: flex;
            justify-content: center;
        }
        .dungeon-grid {
            display: grid;
            gap: 4px; /* Tighter gap for game-board feel */
        }
        .dungeon-cell {
            width: 3.5rem;
            height: 3.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            border-radius: 0.375rem;
            border: 1px solid #374151;
            background-color: #1f2937;
            cursor: pointer;
            user-select: none;
            transition: filter 0.1s;
        }
        .dungeon-cell:hover {
            filter: brightness(1.3);
        }
        /* Specific Tile Stylings */
        .cell-S { background-color: rgba(30, 58, 138, 0.8); border-color: #3b82f6; }
        .cell-E { background-color: rgba(113, 63, 18, 0.8); border-color: #eab308; }
        .cell-wall { background-color: rgba(69, 10, 10, 0.5); border-color: rgba(185, 28, 28, 0.6); }
        .cell-path { background-color: #1f2937; border-color: #4b5563; }
    </style>

    <div
        x-data="dungeonGridBuilder({
            state: $wire.{{ $applyStateBindingModifiers('entangle(\'' . $getStatePath() . '\')') }}
        })"
        class="dungeon-builder"
    >
        <div class="dungeon-controls">
            <div style="display: flex; gap: 1rem; align-items: center;">
                <label style="font-family: monospace; font-size: 0.875rem;">Rows:
                    <input type="number" min="3" max="15" x-model.number="rows" @change="resizeGrid()" class="dungeon-input">
                </label>
                <label style="font-family: monospace; font-size: 0.875rem;">Cols:
                    <input type="number" min="3" max="15" x-model.number="cols" @change="resizeGrid()" class="dungeon-input">
                </label>
            </div>

            <div style="font-size: 0.875rem; color: #9ca3af; font-family: monospace;">
                Click cells to paint. Ensure both <span style="color: #60a5fa; font-weight: bold;">🎬 Start</span> and <span style="color: #facc15; font-weight: bold;">🏆 End</span> exist.
            </div>
        </div>

        <div class="dungeon-palette">
            <template x-for="(tile, key) in palette">
                <button
                    type="button"
                    @click="activeBrush = key"
                    class="dungeon-brush"
                    :class="activeBrush === key ? 'is-active' : ''"
                >
                    <span x-text="tile.icon" style="font-size: 1.25rem;"></span>
                    <span x-text="tile.label"></span>
                </button>
            </template>
        </div>

        <div class="dungeon-canvas-wrapper">
            <div
                class="dungeon-grid"
                :style="'grid-template-columns: repeat(' + cols + ', 3.5rem)'"
            >
                <template x-for="(cell, index) in grid">
                    <button
                        type="button"
                        @click="paintCell(index)"
                        class="dungeon-cell"
                        :class="{
                            'cell-S': cell === 'S',
                            'cell-E': cell === 'E',
                            'cell-wall': cell === '#',
                            'cell-path': cell === '.'
                        }"
                    >
                        <span x-text="palette[cell]?.icon || ''"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('dungeonGridBuilder', ({ state }) => ({
                state: state,
                rows: 5,
                cols: 5,
                grid: [],
                activeBrush: '.',

                palette: {
                    'S': { label: 'Start Portal', icon: '🎬' },
                    '.': { label: 'Stone Path', icon: '🟩' },
                    '#': { label: 'Brick Wall', icon: '🧱' },
                    'E': { label: 'Loot Chest', icon: '🏆' },
                },

                init() {
                    this.parseStringToGrid();

                    this.$watch('state', (newValue) => {
                        if (newValue && newValue !== this.serializeGridToString()) {
                            this.parseStringToGrid();
                        }
                    });
                },

                parseStringToGrid() {
                    if (!this.state) return;

                    const lines = this.state.trim().split('\n');
                    this.rows = lines.length;
                    this.cols = lines[0] ? lines[0].trim().split(/\s+/).length : 5;

                    let newGrid = [];
                    lines.forEach(line => {
                        const tokens = line.trim().split(/\s+/);
                        tokens.forEach(token => {
                            if (token) newGrid.push(token);
                        });
                    });
                    this.grid = newGrid;
                },

                serializeGridToString() {
                    let out = "";
                    for (let r = 0; r < this.rows; r++) {
                        let rowTokens = [];
                        for (let c = 0; c < this.cols; c++) {
                            rowTokens.push(this.grid[(r * this.cols) + c] || '.');
                        }
                        out += rowTokens.join(' ') + (r < this.rows - 1 ? '\n' : '');
                    }
                    return out;
                },

                paintCell(index) {
                    if (this.activeBrush === 'S' || this.activeBrush === 'E') {
                        const existingIdx = this.grid.indexOf(this.activeBrush);
                        if (existingIdx !== -1) {
                            this.grid[existingIdx] = '.';
                        }
                    }

                    this.grid[index] = this.activeBrush;
                    this.state = this.serializeGridToString();
                },

                resizeGrid() {
                    if (this.rows < 3) this.rows = 3;
                    if (this.rows > 15) this.rows = 15;
                    if (this.cols < 3) this.cols = 3;
                    if (this.cols > 15) this.cols = 15;

                    let newGrid = [];
                    for (let r = 0; r < this.rows; r++) {
                        for (let c = 0; c < this.cols; c++) {
                            const oldIndex = (r * this.cols) + c;
                            newGrid.push(this.grid[oldIndex] || '.');
                        }
                    }
                    this.grid = newGrid;
                    this.state = this.serializeGridToString();
                }
            }));
        });
    </script>
</x-dynamic-component>
