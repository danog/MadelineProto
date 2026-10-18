/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * The surface of tweb's `lottieLoader` and `LottiePlayer` the monkeys use, on lottie-web: tweb renders
 * with its own WASM player in a worker, which is tied to the rest of its app. The animations are the
 * same files (`assets/tweb/tgs/*.json`), fetched from the server with `assetUrl`.
 */

import lottie from 'lottie-web/build/player/lottie_light_canvas';
import type {AnimationItem} from 'lottie-web';

export type LottieAssetName = 'TwoFactorSetupMonkeyIdle' | 'TwoFactorSetupMonkeyTracking' | 'TwoFactorSetupMonkeyPeek';

type EnterFrameListener = (frame: number) => void;

export class LottiePlayer {
  /** tweb's player draws into canvases; here, the element holding lottie-web's, toggled the same way. */
  public canvas: HTMLElement[];
  public direction: 1 | -1 = 1;
  private listeners: EnterFrameListener[] = [];
  private lastFrame = -1;
  private resizeObserver: ResizeObserver | undefined;

  constructor(public animation: AnimationItem, element: HTMLElement) {
    this.canvas = [element];
    // lottie-web sizes its canvas from the container when it loads: the cards build theirs before they're
    // in the page (and the monkeys hide one player of two), which gives it no size. Resized once it has one
    if(typeof(ResizeObserver) !== 'undefined') {
      this.resizeObserver = new ResizeObserver(() => {
        if(element.offsetWidth) animation.resize();
      });
      this.resizeObserver.observe(element);
    }
    animation.addEventListener('enterFrame', () => {
      const frame = Math.round(animation.currentFrame);
      if(frame === this.lastFrame) return;
      this.lastFrame = frame;
      this.listeners.forEach((listener) => listener(frame));
    });
  }

  public addEventListener(_event: 'enterFrame', listener: EnterFrameListener) {
    this.listeners.push(listener);
  }

  public play() {
    this.animation.play();
  }

  public pause() {
    this.animation.pause();
  }

  public stop(_renderFirstFrame?: boolean) {
    this.animation.stop();
    this.lastFrame = 0;
  }

  public setDirection(direction: 1 | -1) {
    this.direction = direction;
    this.animation.setDirection(direction);
  }

  public setSpeed(speed: number) {
    this.animation.setSpeed(speed);
  }

  get curFrame() {
    return Math.round(this.animation.currentFrame);
  }

  set curFrame(frame: number) {
    this.animation.goToAndStop(frame, true);
    this.lastFrame = frame;
  }

  public remove() {
    this.resizeObserver?.disconnect();
    this.listeners = [];
    this.animation.destroy();
    this.canvas[0].remove();
  }
}

let assetUrl = '';
const assetCache: Map<LottieAssetName, Promise<any>> = new Map();

const lottieLoader = {
  setAssetUrl(url: string) {
    assetUrl = url;
  },

  /** Fetches an animation (once): the cards preload theirs before they mount. */
  loadAnimationFromURLManually(name: LottieAssetName): Promise<any> {
    let promise = assetCache.get(name);
    if(!promise) {
      promise = fetch(assetUrl + 'assets/tweb/tgs/' + name + '.json').then((response) => {
        if(!response.ok) throw new Error('HTTP ' + response.status);
        return response.json();
      });
      // a failed fetch is retried on the next load
      promise.catch(() => assetCache.delete(name));
      assetCache.set(name, promise);
    }

    return promise;
  },

  async loadAnimationAsAsset(options: {
    container: HTMLElement,
    loop?: boolean,
    autoplay?: boolean,
    width: number,
    height: number,
    noCache?: boolean
  }, name: LottieAssetName): Promise<LottiePlayer> {
    const data = await lottieLoader.loadAnimationFromURLManually(name);
    const element = document.createElement('div');
    element.classList.add('rlottie');
    element.style.width = options.width + 'px';
    element.style.height = options.height + 'px';
    options.container.append(element);

    const animation = lottie.loadAnimation({
      container: element,
      renderer: 'canvas',
      loop: options.loop ?? false,
      autoplay: options.autoplay ?? false,
      // lottie-web mutates the data it plays: every player gets its own copy
      animationData: structuredClone(data),
      rendererSettings: {
        clearCanvas: true,
        // sharp on high density screens, as tweb's player
        dpr: window.devicePixelRatio || 1,
        preserveAspectRatio: 'xMidYMid meet'
      }
    });

    return new LottiePlayer(animation, element);
  },

  waitForFirstFrame(player: LottiePlayer): Promise<void> {
    if(player.animation.isLoaded) {
      return Promise.resolve();
    }

    return new Promise((resolve) => {
      player.animation.addEventListener('DOMLoaded', () => resolve());
    });
  }
};

export type LottieLoader = typeof lottieLoader;

export default lottieLoader;
