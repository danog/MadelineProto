import {cleanup, render} from '@solidjs/testing-library';
import {afterEach, describe, expect, it, vi} from 'vitest';

import LiquidGlass, {canRefract, REFRACTION_FILTER_ID} from '@components/liquidGlass';

function setUserAgent(userAgent: string, brands?: {brand: string}[]) {
  vi.spyOn(navigator, 'userAgent', 'get').mockReturnValue(userAgent);
  Object.defineProperty(navigator, 'userAgentData', {value: brands ? {brands} : undefined, configurable: true});
}

function setMedia(matches: (query: string) => boolean) {
  window.matchMedia = ((query: string) => ({matches: matches(query), addEventListener() {}, removeEventListener() {}})) as any;
}

const chrome = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36';
const safari = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0 Safari/605.1.15';
const firefox = 'Mozilla/5.0 (X11; Linux x86_64; rv:144.0) Gecko/20100101 Firefox/144.0';
const iosChrome = 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/142.0 Mobile/15E148 Safari/604.1';

describe('liquid glass', () => {
  afterEach(() => {
    cleanup();
    vi.restoreAllMocks();
    document.documentElement.classList.remove('glass-refraction');
    document.documentElement.style.removeProperty('--glass-x');
    document.documentElement.style.removeProperty('--glass-y');
  });

  it('refracts only where backdrop-filter takes an SVG filter (Chromium), and not for less transparency', () => {
    (globalThis as any).CSS = {supports: () => true};
    setMedia(() => false);

    setUserAgent(chrome, [{brand: 'Chromium'}, {brand: 'Google Chrome'}]);
    expect(canRefract()).toBe(true);
    setUserAgent(chrome);
    expect(canRefract()).toBe(true);
    // the others would drop the whole backdrop-filter declaration
    for(const userAgent of [safari, firefox, iosChrome]) {
      setUserAgent(userAgent);
      expect(canRefract()).toBe(false);
    }

    setUserAgent(chrome, [{brand: 'Chromium'}]);
    setMedia((query) => query.includes('prefers-reduced-transparency'));
    expect(canRefract()).toBe(false);
  });

  it('renders the light field and the lens, and sets the refraction class', () => {
    (globalThis as any).CSS = {supports: () => true};
    setMedia(() => false);
    setUserAgent(chrome, [{brand: 'Chromium'}]);

    render(() => <LiquidGlass />);
    expect(document.getElementById(REFRACTION_FILTER_ID)?.querySelector('feDisplacementMap')).toBeTruthy();
    expect(document.querySelectorAll('.light-field .light-field-blob')).toHaveLength(4);
    expect(document.querySelector('.light-field')?.getAttribute('aria-hidden')).toBe('true');
    expect(document.documentElement.classList.contains('glass-refraction')).toBe(true);
  });

  it('moves the light with the pointer, once per frame, unless motion is reduced', async () => {
    (globalThis as any).CSS = {supports: () => true};
    setMedia(() => false);
    setUserAgent(firefox);
    const frames: FrameRequestCallback[] = [];
    vi.spyOn(window, 'requestAnimationFrame').mockImplementation((callback) => {
      frames.push(callback);
      return frames.length;
    });

    render(() => <LiquidGlass />);
    window.dispatchEvent(new PointerEvent('pointermove', {clientX: 10, clientY: 20}));
    window.dispatchEvent(new PointerEvent('pointermove', {clientX: 30, clientY: 40}));
    expect(frames).toHaveLength(1);
    frames[0](0);
    expect(document.documentElement.style.getPropertyValue('--glass-x')).toBe('30px');
    expect(document.documentElement.style.getPropertyValue('--glass-y')).toBe('40px');
    expect(document.documentElement.classList.contains('glass-refraction')).toBe(false);

    cleanup();
    document.documentElement.style.removeProperty('--glass-x');
    setMedia((query) => query.includes('prefers-reduced-motion'));
    render(() => <LiquidGlass />);
    window.dispatchEvent(new PointerEvent('pointermove', {clientX: 50, clientY: 60}));
    expect(frames).toHaveLength(1);
  });
});
