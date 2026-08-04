/**
 * The ambient field from amanelle.store's coming-soon page.
 *
 * Two layers: DOM drops (CSS-animated, cheap) and a canvas particle field.
 * Particle physics, counts and alpha curves are the originals. What differs:
 *
 *   - honours prefers-reduced-motion instead of always running
 *   - scales for devicePixelRatio, so the dots are not blurry on retina
 *   - pauses the loop when the tab is hidden or the element scrolls away
 *   - takes its colour from --atmosphere-rgb rather than a hard-coded gold,
 *     so it survives the light theme
 */

const DROP_COUNT = 14;
const PARTICLE_COUNT = 80;

// Phones render the same field over far fewer pixels, so the full counts read
// as clutter and cost battery for no visual gain.
const MOBILE_BREAKPOINT = 640;

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function isCompact() {
    return window.innerWidth < MOBILE_BREAKPOINT;
}

function seedDrops(container) {
    const fragment = document.createDocumentFragment();
    const count = isCompact() ? Math.round(DROP_COUNT * 0.5) : DROP_COUNT;

    for (let i = 0; i < count; i++) {
        const drop = document.createElement('div');
        const size = 14 + Math.random() * 40;

        drop.className = 'drop';
        drop.style.width = `${size}px`;
        drop.style.height = `${size * 1.3}px`;
        drop.style.left = `${Math.random() * 100}%`;
        drop.style.animationDuration = `${18 + Math.random() * 22}s`;

        // Negative delay starts each drop mid-flight, so the field looks
        // established on load instead of every drop rising from the floor
        // together.
        drop.style.animationDelay = `-${Math.random() * 30}s`;

        fragment.appendChild(drop);
    }

    container.appendChild(fragment);
}

class Particle {
    constructor(bounds) {
        this.bounds = bounds;
        this.reset(true);
    }

    reset(initial) {
        const { width, height } = this.bounds;

        this.x = Math.random() * width;
        this.y = initial ? Math.random() * height : height + 10;
        this.r = 0.5 + Math.random() * 1.5;
        this.vy = -(0.15 + Math.random() * 0.4);
        this.vx = (Math.random() - 0.5) * 0.2;
        this.alpha = 0;
        this.maxAlpha = 0.2 + Math.random() * 0.35;
        this.life = 0;
        this.maxLife = 200 + Math.random() * 300;
    }

    update() {
        this.life++;
        this.x += this.vx;
        this.y += this.vy;

        // Fade in over the first 15% of life, hold, fade out over the last
        // 20% — particles never pop in or vanish mid-frame.
        const t = this.life / this.maxLife;

        if (t < 0.15) {
            this.alpha = (t / 0.15) * this.maxAlpha;
        } else if (t > 0.8) {
            this.alpha = ((1 - t) / 0.2) * this.maxAlpha;
        } else {
            this.alpha = this.maxAlpha;
        }

        if (this.life >= this.maxLife) {
            this.reset(false);
        }
    }

    draw(ctx, rgb) {
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(${rgb}, ${this.alpha})`;
        ctx.fill();
    }
}

function startParticles(canvas) {
    const ctx = canvas.getContext('2d');
    const bounds = { width: 0, height: 0 };
    let particles = [];
    let frame = null;
    let visible = true;

    function readColour() {
        const raw = getComputedStyle(document.documentElement)
            .getPropertyValue('--atmosphere-rgb')
            .trim();

        // The custom property is space-separated for rgb(r g b / a); canvas
        // still wants commas.
        return raw ? raw.replace(/\s+/g, ', ') : '201, 169, 110';
    }

    let colour = readColour();

    function resize() {
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        const rect = canvas.getBoundingClientRect();

        bounds.width = rect.width;
        bounds.height = rect.height;

        canvas.width = Math.round(rect.width * dpr);
        canvas.height = Math.round(rect.height * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    function tick() {
        ctx.clearRect(0, 0, bounds.width, bounds.height);

        for (const particle of particles) {
            particle.update();
            particle.draw(ctx, colour);
        }

        frame = requestAnimationFrame(tick);
    }

    function play() {
        if (frame === null && visible) {
            frame = requestAnimationFrame(tick);
        }
    }

    function pause() {
        if (frame !== null) {
            cancelAnimationFrame(frame);
            frame = null;
        }
    }

    resize();

    const count = isCompact() ? Math.round(PARTICLE_COUNT * 0.45) : PARTICLE_COUNT;

    particles = Array.from({ length: count }, () => new Particle(bounds));

    window.addEventListener('resize', resize);
    document.addEventListener('visibilitychange', () => {
        document.hidden ? pause() : play();
    });

    // Stop burning frames once the hero has scrolled past.
    if ('IntersectionObserver' in window) {
        new IntersectionObserver(([entry]) => {
            visible = entry.isIntersecting;
            visible ? play() : pause();
        }).observe(canvas);
    }

    // The theme toggle swaps --atmosphere-rgb; re-read it when it changes.
    new MutationObserver(() => {
        colour = readColour();
    }).observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });

    play();
}

export default function initAtmosphere() {
    if (prefersReducedMotion()) {
        return;
    }

    document.querySelectorAll('[data-atmosphere]').forEach((container) => {
        // Livewire fires `navigated` on first load as well as on subsequent
        // visits, so without this the field is seeded twice and every count
        // doubles.
        if (container.dataset.atmosphereReady === 'true') {
            return;
        }

        container.dataset.atmosphereReady = 'true';

        seedDrops(container);

        const canvas = container.querySelector('canvas');

        if (canvas) {
            startParticles(canvas);
        }
    });
}
