/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * tweb's `IconTsx`, drawing the glyph as inline SVG (see `icon.ts`).
 */

import {JSX, splitProps} from 'solid-js';
import classNames from '@helpers/string/classNames';
import {iconSvg, IconName} from '@components/icon';

export type IconTsxProps = {
  icon: IconName;
} & JSX.HTMLAttributes<HTMLSpanElement>;

export const IconTsx = (inProps: IconTsxProps) => {
  const [props, rest] = splitProps(inProps, ['icon', 'class']);
  return (
    <span aria-hidden="true" class={classNames('tgico', 'tgico-' + props.icon, props.class)} {...rest}>
      {iconSvg(props.icon)}
    </span>
  );
};
