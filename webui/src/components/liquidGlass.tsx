/*
 * MadelineProto's liquid glass: the light field behind the panes, the SVG lens that refracts it, and the light
 * that follows the pointer. The material itself is in scss/glass/.
 */

import {JSX, onCleanup, onMount} from 'solid-js';

/** The id of the SVG filter refracting the backdrop of the panes (scss/glass/_material.scss). */
export const REFRACTION_FILTER_ID = 'mp-glass-refraction';

/**
 * Whether the backdrop of the panes can be refracted: only Chromium applies an SVG filter in backdrop-filter
 * (the others drop the whole declaration, so it's set behind `html.glass-refraction`), and not when the user
 * asks for less transparency or more contrast (the panes are then near-opaque, see scss/glass/_tokens.scss).
 */
export function canRefract(): boolean {
  const brands = (navigator as any).userAgentData?.brands as {brand: string}[] | undefined;
  // Safari's and Firefox's user agents have no Chrome/ (iOS browsers are all WebKit: CriOS, not Chrome/)
  const chromium = brands ? brands.some(({brand}) => brand === 'Chromium') : /\bChrom(e|ium)\/\d+/.test(navigator.userAgent);
  const plain = window.matchMedia?.('(prefers-reduced-transparency: reduce), (prefers-contrast: more)').matches;
  return chromium && !plain && typeof CSS !== 'undefined' && CSS.supports('backdrop-filter', 'blur(1px)');
}

/** The light following the pointer: --glass-x / --glass-y on <html>, at most once per frame. */
function followPointer(): () => void {
  if(window.matchMedia?.('(prefers-reduced-motion: reduce), (hover: none)').matches) {
    return () => {};
  }

  const root = document.documentElement;
  let frame = 0;
  let x = 0, y = 0;
  const onMove = (event: PointerEvent) => {
    x = event.clientX;
    y = event.clientY;
    if(!frame) {
      frame = requestAnimationFrame(() => {
        frame = 0;
        root.style.setProperty('--glass-x', x + 'px');
        root.style.setProperty('--glass-y', y + 'px');
      });
    }
  };
  window.addEventListener('pointermove', onMove, {passive: true});
  return () => {
    window.removeEventListener('pointermove', onMove);
    cancelAnimationFrame(frame);
  };
}

/**
 * The light field and the lens: rendered behind the auth flow while the glass is on (helpers/glass.ts).
 *
 * The lens displaces the backdrop with low-frequency noise, so the hairlines of the light field bend where they
 * pass under the edges of a pane; it's mild enough to leave the blurred backdrop calm in the middle.
 */
export default function LiquidGlass(): JSX.Element {
  onMount(() => {
    document.documentElement.classList.toggle('glass-refraction', canRefract());
    onCleanup(followPointer());
  });
  onCleanup(() => {
    document.documentElement.classList.remove('glass-refraction');
  });

  return (
    <>
      <svg class="glass-defs" aria-hidden="true">
        <filter
          id={REFRACTION_FILTER_ID}
          x="0"
          y="0"
          width="100%"
          height="100%"
          color-interpolation-filters="sRGB"
        >
          <feTurbulence type="fractalNoise" baseFrequency="0.006 0.009" numOctaves="2" seed="7" result="noise" />
          <feGaussianBlur in="noise" stdDeviation="2.5" result="lens" />
          <feDisplacementMap in="SourceGraphic" in2="lens" scale="34" xChannelSelector="R" yChannelSelector="G" />
        </filter>
      </svg>
      <div class="light-field" aria-hidden="true">
        <span class="light-field-blob" />
        <span class="light-field-blob" />
        <span class="light-field-blob" />
        <span class="light-field-blob" />
        <div class="light-field-texture" />
      </div>
    </>
  );
}
