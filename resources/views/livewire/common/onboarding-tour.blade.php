<div>
    @if($steps && !$isCompleted)
        <div
            x-data="onboardingTour({
                steps: @js($steps),
                currentStepIndex: @entangle('currentStepIndex'),
                isOpen: @entangle('isOpen'),
            })"
            x-cloak
        >
            <template x-teleport="body">
                <div
                    x-show="visible"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-[9999] pointer-events-none"
                    style="display:none;"
                >
                    {{-- Spotlight cutout with full-screen dim via box-shadow --}}
                    <div
                        x-show="spotlight !== null"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        :style="spotlight"
                        class="fixed rounded-lg ring-4 ring-indigo-400/80 pointer-events-none"
                        style="box-shadow: 0 0 0 9999px rgba(0,0,0,0.55);"
                    >
                        <span
                            x-show="needsClick"
                            class="absolute inline-flex h-full w-full animate-ping rounded-lg bg-indigo-400/20 pointer-events-none"
                        ></span>
                    </div>

                    {{-- Full dim backdrop (center steps with no target) --}}
                    <div
                        x-show="spotlight === null"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        class="fixed inset-0 bg-black/55 pointer-events-none"
                    ></div>

                    {{-- Tooltip card --}}
                    <div
                        x-show="visible"
                        x-transition:enter="transition ease-out duration-300 delay-100"
                        x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        :style="tooltip"
                        class="fixed z-[10001] pointer-events-auto w-[370px] max-w-[90vw]"
                    >
                        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-2xl border border-neutral-200/80 dark:border-neutral-700 overflow-hidden">
                            {{-- Progress bar --}}
                            <div class="h-1 bg-neutral-100 dark:bg-neutral-700/80">
                                <div
                                    class="h-full bg-gradient-to-r from-indigo-500 to-violet-500 rounded-r-full"
                                    :style="`width:${progress}%;transition:width 0.5s ease-out`"
                                ></div>
                            </div>

                            {{-- Content --}}
                            <div class="px-5 pt-4 pb-3">
                                <p class="text-[11px] font-semibold text-indigo-500/70 dark:text-indigo-400/70 uppercase tracking-wider mb-2.5">
                                    Step <span x-text="currentStepIndex + 1"></span>
                                    <span class="text-neutral-300 dark:text-neutral-600 mx-0.5">/</span>
                                    <span x-text="steps.length"></span>
                                </p>
                                <h3 class="text-[15px] font-semibold text-neutral-900 dark:text-white leading-snug mb-1" x-text="currentStep?.title"></h3>
                                <p class="text-[13px] text-neutral-500 dark:text-neutral-400 leading-relaxed" x-text="currentStep?.content"></p>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center justify-between px-5 py-2.5 bg-neutral-50 dark:bg-neutral-800/60 border-t border-neutral-100 dark:border-neutral-700/50">
                                <div>
                                    <button
                                        x-show="currentStepIndex > 0"
                                        @click="goBack()"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-neutral-500 dark:text-neutral-400 hover:text-neutral-800 dark:hover:text-white rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                                        Back
                                    </button>
                                </div>

                                <div class="flex items-center gap-2">
                                    {{-- Finish (last step) --}}
                                    <button
                                        x-show="currentStepIndex === steps.length - 1"
                                        wire:click="completeTour"
                                        class="inline-flex items-center gap-1.5 px-4 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-500 rounded-lg transition-colors shadow-sm"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                        Finish Tour
                                    </button>

                                    {{-- Click instruction --}}
                                    <span
                                        x-show="needsClick && currentStepIndex < steps.length - 1"
                                        class="text-[11px] text-indigo-400 dark:text-indigo-300 font-medium italic select-none flex items-center gap-1"
                                    >
                                        <svg class="w-3 h-3 animate-bounce" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672z"/></svg>
                                        Click the highlighted item
                                    </span>

                                    {{-- Next button --}}
                                    <button
                                        x-show="!needsClick && currentStepIndex < steps.length - 1"
                                        @click="goNext()"
                                        class="inline-flex items-center gap-1 px-4 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg transition-colors shadow-sm"
                                    >
                                        Next
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Dismiss button --}}
                    <button
                        wire:click="dismissTour"
                        class="fixed top-4 right-4 z-[10002] p-2 rounded-full bg-white/10 hover:bg-white/25 text-white/80 hover:text-white transition-all backdrop-blur-sm pointer-events-auto"
                        title="Dismiss tour (progress saved)"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </template>
        </div>
    @endif

    <style>[x-cloak]{display:none!important}</style>
</div>

@script
<script>
Alpine.data('onboardingTour', ({ steps, currentStepIndex, isOpen }) => ({
    steps,
    currentStepIndex,
    isOpen,
    visible: false,
    spotlight: null,
    tooltip: '',
    interactionCompleted: false,
    _handler: null,
    _target: null,
    _resize: null,
    _scroll: null,
    _timer: null,
    _advancing: false,
    _setupIdx: -1,
    _lastBackAt: 0,
    _onNavigated: null,

    get currentStep() {
        return this.steps[this.currentStepIndex] ?? null;
    },

    get progress() {
        return Math.round(((this.currentStepIndex + 1) / this.steps.length) * 100);
    },

    get needsClick() {
        const step = this.currentStep;
        if (!step) return false;
        if (step.interaction !== 'click' && step.interaction !== 'navigate') return false;
        if (!step.target || step.placement === 'center') return false;
        return !this._isSamePage();
    },

    init() {
        sessionStorage.removeItem('onboarding_nav_from');

        const pendingKey = sessionStorage.getItem('onboarding_nav_pending');
        if (pendingKey) {
            sessionStorage.removeItem('onboarding_nav_pending');
            this.$wire.call('advanceAfterNavigation', pendingKey).then(() => {
                setTimeout(() => {
                    this.$wire
                        .call('reloadProgressFromDatabase')
                        .then(() => this.$wire.call('syncTourToPath', window.location.pathname))
                        .then(() => this._show());
                }, 1200);
            });
        } else if (this.isOpen) {
            this.$nextTick(() => this._show());
        }

        this.$watch('isOpen', (v) => {
            if (v) {
                this._advancing = false;
                this.$nextTick(() => this._show());
            } else {
                this._hide();
            }
        });

        this.$watch('currentStepIndex', () => {
            if (!this.visible || !this.isOpen) return;
            this._setupIdx = -1;
            this.$nextTick(() => this._setup());
        });

        this._resize = () => { if (this.visible) this._position(); };
        window.addEventListener('resize', this._resize);

        this._scroll = () => { if (this.visible) this._position(); };
        window.addEventListener('scroll', this._scroll, true);

        this._onNavigated = () => {
            if (!this.isOpen) return;
            if (Date.now() - this._lastBackAt < 5000) return;
            setTimeout(() => {
                this.$nextTick(() => {
                    this.$wire
                        .call('reloadProgressFromDatabase')
                        .then(() => this.$wire.call('syncTourToPath', window.location.pathname))
                        .then(() => {
                            this._setupIdx = -1;
                            this.$nextTick(() => {
                                if (this.visible) this._setup();
                            });
                        });
                });
            }, 200);
        };
        document.addEventListener('livewire:navigated', this._onNavigated);
    },

    destroy() {
        this._hide();
        if (this._timer) clearTimeout(this._timer);
        if (this._resize) window.removeEventListener('resize', this._resize);
        if (this._scroll) window.removeEventListener('scroll', this._scroll, true);
        if (this._onNavigated) document.removeEventListener('livewire:navigated', this._onNavigated);
    },

    // ── Show / Hide ──

    _show() {
        this.visible = true;
        this.$nextTick(() => this._setup());
    },

    _hide() {
        this.visible = false;
        this.spotlight = null;
        this.tooltip = '';
        this._unbind();
    },

    // ── Step setup ──

    _setup() {
        if (this._setupIdx === this.currentStepIndex && this.visible) return;
        this._setupIdx = this.currentStepIndex;
        this._unbind();
        this.interactionCompleted = false;

        const step = this.currentStep;
        if (!step) return;

        const attach = () => {
            this._position();
            if (this.needsClick && step.target) {
                const el = this._el(step.target);
                if (el) this._bind(el);
            }
        };

        const sel = step.wait_for || step.target;
        sel ? this._poll(sel, attach) : attach();
    },

    _position() {
        this._posSpotlight();
        this._posTooltip();
    },

    // ── Element resolution ──

    _el(selector) {
        if (!selector) return null;
        const all = Array.from(document.querySelectorAll(selector));
        if (!all.length) return null;
        if (all.length === 1) return all[0];

        let best = null, hi = -1;
        for (const el of all) {
            const r = el.getBoundingClientRect();
            const s = getComputedStyle(el);
            let sc = 0;
            if (r.width > 0 && r.height > 0) sc += 3;
            if (s.display !== 'none' && s.visibility !== 'hidden' && parseFloat(s.opacity) > 0) sc += 3;
            const inView = r.right > 0 && r.bottom > 0 && r.left < window.innerWidth && r.top < window.innerHeight;
            if (inView) sc += 4;
            if (r.left >= 0) sc += 1;
            if (r.top >= 0) sc += 1;
            if (sc > hi) { best = el; hi = sc; }
        }
        return best ?? all[0];
    },

    _poll(sel, cb, timeout = 5000) {
        const t0 = Date.now();
        const check = () => {
            if (this._el(sel)) { cb(); return; }
            if (Date.now() - t0 > timeout) { cb(); return; }
            requestAnimationFrame(check);
        };
        requestAnimationFrame(check);
    },

    _link(el) {
        return el.querySelector('a[href], button, [wire\\:click], [x-on\\:click]') || el;
    },

    _isSamePage() {
        const el = this._el(this.currentStep?.target);
        if (!el) return false;
        const href = this._href(el);
        if (!href) return false;
        try {
            return new URL(window.location.href).pathname === new URL(href, window.location.origin).pathname;
        } catch { return false; }
    },

    _href(el) {
        const a = this._link(el);
        return a?.closest?.('a[href]')?.href || a?.href || null;
    },

    // ── Click detection ──

    _bind(el) {
        this._target = el;
        const sel = this.currentStep?.target;

        this._handler = (e) => {
            if (!(e.target instanceof Element)) return;
            const hit = e.target.closest(sel);
            const cur = this._el(sel);
            if (!hit || !cur || hit !== cur) return;
            this._unbind();
            this.interactionCompleted = true;
            this._onClicked();
        };
        document.addEventListener('pointerdown', this._handler, { capture: true });
    },

    _unbind() {
        if (this._handler) {
            document.removeEventListener('pointerdown', this._handler, true);
            this._handler = null;
        }
        this._target = null;
    },

    _onClicked() {
        const el = this._el(this.currentStep?.target);
        const href = el ? this._href(el) : null;
        const navigates = href && !this._isSamePage();

        this._setupIdx = -1;
        this._hide();

        if (navigates) {
            const k = this.currentStep?.key;
            if (k) sessionStorage.setItem('onboarding_nav_pending', k);
        } else {
            this._advancing = true;
            this._advance(1500);
        }
    },

    _advance(delay) {
        if (this._timer) clearTimeout(this._timer);
        this._timer = setTimeout(() => {
            if (!this._advancing) return;
            this._advancing = false;
            this.$wire.call('autoAdvance').then(() => {
                this.$nextTick(() => this._show());
            });
        }, delay);
    },

    // ── Manual navigation ──

    goNext() {
        this._setupIdx = -1;
        this.$wire.call('nextStep').then(() => {
            this._setupIdx = -1;
            this.$nextTick(() => {
                this.$nextTick(() => this._setup());
            });
        });
    },

    goBack() {
        this._lastBackAt = Date.now();
        this._setupIdx = -1;
        this.$wire.call('previousStep').then(() => {
            this._setupIdx = -1;
            this.$nextTick(() => {
                this.$nextTick(() => this._setup());
            });
        });
    },

    // ── Positioning ──

    _posSpotlight() {
        const step = this.currentStep;
        if (!step?.target || step.placement === 'center') {
            this.spotlight = null;
            return;
        }

        const el = this._el(step.target);
        if (!el) { this.spotlight = null; return; }

        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                try {
                    const r = el.getBoundingClientRect();
                    const pad = 5;
                    this.spotlight = `top:${r.top - pad}px;left:${r.left - pad}px;width:${r.width + pad * 2}px;height:${r.height + pad * 2}px;`;
                } catch { this.spotlight = null; }
            });
        });
    },

    _posTooltip() {
        const step = this.currentStep;
        const margin = 12;
        const w = Math.min(370, window.innerWidth - margin * 2);
        const popH = 200;

        if (!step?.target || step.placement === 'center' || window.innerWidth < 640) {
            const l = Math.max(margin, Math.round((window.innerWidth - w) / 2));
            const t = window.innerWidth < 640
                ? Math.max(margin, window.innerHeight - popH - 40)
                : Math.max(margin, Math.round((window.innerHeight - popH) / 2));
            this.tooltip = `top:${t}px;left:${l}px;width:${w}px;`;
            return;
        }

        const el = this._el(step.target);
        if (!el) {
            const l = Math.max(margin, Math.round((window.innerWidth - w) / 2));
            const t = Math.max(margin, Math.round((window.innerHeight - popH) / 2));
            this.tooltip = `top:${t}px;left:${l}px;width:${w}px;`;
            return;
        }

        const r = el.getBoundingClientRect();
        const gap = 12;
        let left, top;

        const fitsRight = r.right + gap + w <= window.innerWidth - margin;
        const fitsLeft  = r.left - gap - w >= margin;
        const fitsBelow = r.bottom + gap + popH <= window.innerHeight - margin;
        const fitsAbove = r.top - gap - popH >= margin;

        if (fitsRight) {
            left = r.right + gap;
            top = Math.max(margin, Math.min(r.top, window.innerHeight - popH - margin));
        } else if (fitsLeft) {
            left = r.left - gap - w;
            top = Math.max(margin, Math.min(r.top, window.innerHeight - popH - margin));
        } else if (fitsBelow) {
            left = Math.max(margin, Math.min(r.left, window.innerWidth - w - margin));
            top = r.bottom + gap;
        } else if (fitsAbove) {
            left = Math.max(margin, Math.min(r.left, window.innerWidth - w - margin));
            top = r.top - gap - popH;
        } else {
            left = Math.max(margin, Math.round((window.innerWidth - w) / 2));
            top = Math.max(margin, Math.round((window.innerHeight - popH) / 2));
        }

        top  = Math.max(margin, Math.min(top, window.innerHeight - popH - margin));
        left = Math.max(margin, Math.min(left, window.innerWidth - w - margin));
        this.tooltip = `top:${Math.round(top)}px;left:${Math.round(left)}px;width:${w}px;`;
    },
}));
</script>
@endscript
