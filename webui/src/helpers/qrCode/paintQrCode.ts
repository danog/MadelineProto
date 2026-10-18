/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * Adapted for MadelineProto: the logo in the middle is MadelineProto's mark.
 */

import pause from '@helpers/schedulers/pause';
import textToSvgURL from '@helpers/textToSvgURL';
import {logoSvgText} from '@components/logo';

/**
 * Builds a Telegram-styled QR canvas for `data` using the supplied palette.
 * 
 *
 * The host element receives the canvas as its last child; existing children
 * (e.g. a preloader) are left in place so the caller can decide when to fade
 * them out, mirroring the legacy `SignQRCard` behaviour.
 *
 * Returns a teardown that strips every QR canvas this call appended, so the
 * caller can reset the host before repainting (e.g. on theme change).
 */
export type PaintQrOptions = {
  data: string;
  size: number;
  host: HTMLElement;
  /** Backdrop color behind the dots (usually the QR card surface color). */
  background: string;
  /** Dot color — the "ink" of the QR code. */
  foreground: string;
  /** Color used to tint the embedded Telegram logo. */
  logoColor: string;
  /**
   * Device-pixel multiplier for the rendered QR bitmap. Defaults to the screen
   * DPR. The My QR popup passes max(screenDPR, 3) so the QR stays crisp in its
   * 3×-scale (1170×2532) export PNG even on 1×/2× displays.
   */
  pixelRatio?: number;
  /**
   * Class added to the QR canvas the instant it's appended — before the draw
   * race is awaited — so the canvas is already correctly sized while qr-code-
   * styling finishes loading the logo (the auth `SignQRCard` reveal relies on
   * the canvas filling its container during that window).
   */
  canvasClass?: string;
  /** Already-loaded `qr-code-styling` constructor; loaded lazily by the caller. */
  QRCodeStylingCtor: any;
};

// The logo in the middle of the code: MadelineProto's mark (tweb: Telegram's, fetched and recoloured), as a
// data URL per colour
const logoUrlCache = new Map<string, Promise<string>>();
function getLogoUrl(logoColor: string): Promise<string> {
  let url = logoUrlCache.get(logoColor);
  if(!url) {
    url = Promise.resolve(textToSvgURL(logoSvgText(logoColor)));
    logoUrlCache.set(logoColor, url);
  }
  return url;
}

export async function paintQrCode(options: PaintQrOptions) {
  const {data, size, host, background, foreground, logoColor, pixelRatio = window.devicePixelRatio, QRCodeStylingCtor} = options;

  const logoUrl = await getLogoUrl(logoColor);

  const qrCode = new QRCodeStylingCtor({
    width: size * pixelRatio,
    height: size * pixelRatio,
    data,
    image: logoUrl,
    dotsOptions: {color: foreground, type: 'rounded'},
    cornersSquareOptions: {type: 'extra-rounded', color: foreground},
    imageOptions: {imageSize: 1, margin: 0},
    backgroundOptions: {color: background},
    qrOptions: {errorCorrectionLevel: 'L'}
  });

  qrCode.append(host);
  const canvas = host.lastChild as HTMLCanvasElement;
  if(options.canvasClass) canvas.classList.add(options.canvasClass);

  // qr-code-styling races the image-load against a 1s upper bound — matches the
  // legacy behaviour so we don't leave the host stuck behind a never-loading logo.
  let drawingPromise: Promise<void>;
  const internalDrawingPromise = qrCode._drawingPromise || qrCode._canvasDrawingPromise;
  if(internalDrawingPromise) {
    drawingPromise = internalDrawingPromise;
  } else {
    const image = qrCode._canvas?._image;
    drawingPromise = Promise.race([
      pause(1000),
      new Promise<void>((resolve) => {
        if(!image || image.complete) {
          window.requestAnimationFrame(() => resolve());
          return;
        }

        image.addEventListener('load', () => {
          window.requestAnimationFrame(() => resolve());
        }, {once: true});
      })
    ]);
  }
  await drawingPromise;

  return {canvas, qrCode};
}
