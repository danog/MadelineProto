/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 */

// @ts-ignore
const IS_TOUCH_SUPPORTED = ('ontouchstart' in window) || (window.DocumentTouch && document instanceof DocumentTouch)/*  || true */;
export default IS_TOUCH_SUPPORTED;
