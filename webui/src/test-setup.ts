// What jsdom lacks, and the cards use: element sizes (ResizeObserver: AutoHeight, the lottie players) and
// smooth scrolling (the country picker).

class ResizeObserverStub {
  observe() {}
  unobserve() {}
  disconnect() {}
}

globalThis.ResizeObserver ??= ResizeObserverStub as unknown as typeof ResizeObserver;
Element.prototype.scrollTo ??= function() {};
Element.prototype.scrollIntoView ??= function() {};
window.matchMedia ??= ((query: string) => ({
  matches: false,
  media: query,
  onchange: null,
  addEventListener() {},
  removeEventListener() {},
  addListener() {},
  removeListener() {},
  dispatchEvent: () => false
})) as typeof window.matchMedia;

// * the reduced motion level: the cards replace each other at once (jsdom never ends a CSS transition)
document.body.classList.add('animation-level-0');
