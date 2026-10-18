/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * Adapted for MadelineProto.
 */

import {Accessor, createMemo, createSignal, JSX, Ref, Setter} from 'solid-js';
import {FormatterArguments, i18n, LangPackKey} from '@lib/langPack';
import {IconTsx} from '@components/iconTsx';
import type {IconName} from '@components/icon';
import classNames from '@helpers/string/classNames';
import RippleElement from '@components/rippleElement';

type ButtonAccessibilityProps = Pick<JSX.ButtonHTMLAttributes<HTMLButtonElement>,
  | 'aria-hidden'
  | 'aria-label'
  | 'aria-pressed'
  | 'aria-expanded'
  | 'aria-controls'
  // Native, non-delegated listeners. Needed wherever an ancestor cancels the
  // event before it reaches the document listener Solid delegates from — a row
  // inside `AppSelectPeers`, say (appSelectPeers.ts:420).
  | 'on:click'
  | 'on:keydown'
>;

const Button = (props: Partial<{
  ref: Ref<HTMLElement>,
  as: 'a' | 'div' | 'button',
  class: string,
  disabled: boolean,
  primaryFilled: boolean,
  primary: boolean,
  primaryTransparent: boolean,
  large: boolean,
  children: JSX.Element,
  icon: IconName,
  iconAfter: IconName,
  iconClass: string,
  onClick: (e: MouseEvent) => any,
  text: LangPackKey,
  textArgs: FormatterArguments,
  noRipple: boolean,
  rippleSquare: boolean,
  onlyMobile: boolean,
  tabIndex: number
}> & ButtonAccessibilityProps = {}): JSX.Element => {
  let disabled: Accessor<boolean>, setDisabled: Setter<boolean>;
  if(props.disabled !== undefined) {
    disabled = createMemo(() => props.disabled);
  } else {
    [disabled, setDisabled] = createSignal(false);
  }

  return (
    <RippleElement
      ref={props.ref as Ref<any>}
      component={props.as || 'button'}
      type={!props.as || props.as === 'button' ? 'button' : undefined}
      class={classNames(
        props.class,
        props.primaryFilled && 'btn-primary btn-color-primary',
        props.primary && 'btn btn-primary primary',
        props.primaryTransparent && 'btn-primary primary btn-transparent',
        props.large && 'btn-large',
        props.onlyMobile && 'only-handhelds'
      )}
      disabled={disabled()}
      onClick={props.onClick && setDisabled ? ((e: any) => {
        try {
          const result = props.onClick(e);
          if(result instanceof Promise) {
            setDisabled(true);
            result.finally(() => {
              setDisabled(false);
            });
          }
        } catch(err) {
          throw err;
        }
      }) : props.onClick}
      noRipple={props.noRipple}
      rippleSquare={props.rippleSquare}
      tabIndex={props.tabIndex}
      aria-hidden={props['aria-hidden']}
      aria-label={props['aria-label']}
      aria-pressed={props['aria-pressed']}
      aria-expanded={props['aria-expanded']}
      aria-controls={props['aria-controls']}
      on:click={props['on:click']}
      on:keydown={props['on:keydown']}
    >
      {props.icon && <IconTsx icon={props.icon} class={classNames('button-icon', props.iconClass)} />}
      {props.text ? i18n(props.text, props.textArgs) : props.children}
      {props.iconAfter && <IconTsx icon={props.iconAfter} class={classNames('button-icon', props.iconClass)} />}
    </RippleElement>
  );
};

Button.Corner = (props: Partial<{
  ref: Ref<HTMLElement>,
  children: JSX.Element,
  onClick: (e: MouseEvent) => void,
  class: string,
  icon: IconName,
  disabled: boolean,
  tabIndex: number
}> & ButtonAccessibilityProps) => {
  return (
    <Button
      {...props}
      class={classNames('btn-circle', 'btn-corner', 'z-depth-1', props.class)}
      tabIndex={props.tabIndex}
    />
  );
};

Button.Icon = (props: {icon: IconName} & Partial<{
  ref: Ref<HTMLElement>,
  children: JSX.Element,
  onClick: (e: MouseEvent) => void,
  class: string,
  disabled: boolean,
  noRipple: boolean,
  tabIndex: number
}> & ButtonAccessibilityProps) => {
  return (
    <Button
      {...props}
      class={classNames('btn-icon', props.icon, props.class)}
      aria-label={props['aria-label']}
      tabIndex={props.tabIndex}
    />
  );
};

export default Button;
