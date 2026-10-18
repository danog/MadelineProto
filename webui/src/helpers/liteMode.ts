/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * tweb's `liteMode`, reduced to what the auth flow asks: whether animations are on. They follow the
 * animation level on <body> (`animation-level-0` when the system asks for reduced motion).
 */

const liteMode = {
  isAvailable(_type: 'animations') {
    return !document.body.classList.contains('animation-level-0');
  }
};

export default liteMode;
