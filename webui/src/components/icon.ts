/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * tweb's `Icon()`, drawing the glyph as inline SVG instead of with the icon font (see `iconPaths.ts`).
 * Same element: a `span.tgico`, hidden from assistive tech, sized by the font size.
 */

import {ICON_PATHS, IconName} from '@components/iconPaths';

export type {IconName};

// * the arrows point the way the text runs
const MIRRORED: Set<IconName> = new Set(['next', 'previous', 'back']);

export function iconSvg(icon: IconName) {
  const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  // the font's glyphs have their y axis up: flipped into the box
  svg.setAttribute('viewBox', '0 0 1024 1024');
  const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
  path.setAttribute('d', ICON_PATHS[icon]);
  path.setAttribute('transform', 'matrix(1 0 0 -1 0 1024)');
  svg.append(path);
  return svg;
}

export default function Icon(icon: IconName, ...classes: string[]) {
  const span = document.createElement('span');
  span.classList.add('tgico', 'tgico-' + icon, ...classes.filter(Boolean));
  if(MIRRORED.has(icon)) span.classList.add('tgico-mirror');
  span.append(iconSvg(icon));
  // The glyph is meaningless to assistive tech: the accessible name comes from the parent control.
  span.setAttribute('aria-hidden', 'true');
  return span;
}
