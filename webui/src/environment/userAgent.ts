/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * Adapted for MadelineProto.
 */

const ctx = window;

export const USER_AGENT = navigator ? navigator.userAgent : null;
export const IS_APPLE = navigator.userAgent.search(/OS X|iPhone|iPad|iOS/i) !== -1;
export const IS_ANDROID = navigator.userAgent.toLowerCase().indexOf('android') !== -1;
export const IS_CHROMIUM = /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor);
export const CHROMIUM_VERSION = (() => {
  try {
    return +navigator.userAgent.match(/Chrom(?:e|ium)\/(.+?)(?:\s|\.)/)[1];
  } catch(err) {
  }
})();

// https://stackoverflow.com/a/58065241
export const IS_APPLE_MOBILE = (/iPad|iPhone|iPod/.test(navigator.platform) ||
  (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)) &&
  !(ctx as any).MSStream;

export const IS_SAFARI = !!('safari' in ctx) || !!(USER_AGENT && (/\b(iPad|iPhone|iPod)\b/.test(USER_AGENT) || (!!USER_AGENT.match('Safari') && !USER_AGENT.match('Chrome'))))/*  || true */;
export const IS_FIREFOX = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;

export const IS_MOBILE_SAFARI = IS_SAFARI && IS_APPLE_MOBILE;

export const IS_MOBILE = (navigator.maxTouchPoints === undefined || navigator.maxTouchPoints > 0) && navigator.userAgent.search(/iOS|iPhone OS|Android|BlackBerry|BB10|Series ?[64]0|J2ME|MIDP|opera mini|opera mobi|mobi.+Gecko|Windows Phone/i) != -1;
