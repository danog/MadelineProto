/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * Adapted for MadelineProto: only `isMobile` (tweb's handhelds breakpoint, $small-screen in variables.scss),
 * which the cards read to size their stickers.
 */

const mediaSizes = {
  get isMobile() {
    return !!window.matchMedia?.('(max-width: 600px)').matches;
  }
};

export default mediaSizes;
