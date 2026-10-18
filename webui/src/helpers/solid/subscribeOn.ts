/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * Adapted for MadelineProto.
 */

import {onCleanup} from 'solid-js';
type ListenerElement = EventTarget;

export function subscribeOn<T extends ListenerElement>(obj: T): T['addEventListener'] {
  return ((event: string, callback: Function, options: any) => {
    // @ts-ignore
    obj.addEventListener(event, callback, options);

    onCleanup(() => {
      // @ts-ignore
      obj.removeEventListener(event, callback, options);
    });
  }) as any;
}
