/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * Adapted for MadelineProto.
 */

import styles from '@components/codeInputField.module.scss';
import I18n, {LangPackKey} from '@lib/langPack';
import classNames from '@helpers/string/classNames';
import {children, createRoot, createSignal, Index, Ref, Show, Signal} from 'solid-js';
import {subscribeOn} from '@helpers/solid/subscribeOn';
import {Transition} from 'solid-transition-group';

export default class CodeInputFieldCompat {
  public container: HTMLDivElement;
  public input: HTMLInputElement;
  private dispose: () => void;
  private errorSignal!: Signal<boolean>;
  private disabledSignal!: Signal<boolean>;
  private valueSignal!: Signal<string>;

  constructor(public options: {
    length: number
    onChange?: (code: string) => void
    onFill?: (code: string) => void,
    class?: string,
    label?: LangPackKey
  }) {
    this.errorSignal = createSignal(false);
    this.disabledSignal = createSignal(false);
    this.valueSignal = createSignal('');
    this.container = createRoot((dispose) => {
      this.dispose = dispose;
      const el = (
        <CodeInputField
          {...options}
          ref={this.input}
          error={this.errorSignal[0]()}
          disabled={this.disabledSignal[0]()}
          valueSignal={this.valueSignal}
        />
      );
      return children(() => el)() as HTMLDivElement;
    });
  }

  set error(value: boolean) {
    this.errorSignal[1](value);
  }

  set disabled(value: boolean) {
    this.disabledSignal[1](value);
  }

  set value(value: string) {
    this.valueSignal[1](value);
  }

  get value() {
    return this.valueSignal[0]();
  }

  cleanup() {
    this.dispose();
  }
}

export function CodeInputField(props: {
  valueSignal?: Signal<string>
  ref?: Ref<HTMLInputElement>
  class?: string
  length: number
  onChange?: (code: string) => void
  onFill?: (code: string) => void
  error?: boolean
  disabled?: boolean
  /** Accessible name — the boxes are the only visible affordance, there is no visible label. */
  label?: LangPackKey
}) {
  const [value, setValue] = props.valueSignal ?? createSignal('');
  const [activeIndexStart, setActiveIndexStart] = createSignal(-1);
  const [activeIndexEnd, setActiveIndexEnd] = createSignal(-1);
  const [isInserting, setIsInserting] = createSignal(false);

  let isFocused = false;
  let isShiftKeyDown = false;
  const previousSelection = {
    inserting: false as boolean,
    start: null as number | null,
    end: null as number | null
  };

  const syncSelection = (props: {
    start: number | null
    end: number | null
    inserting: boolean
    originalStart: number | null
    originalEnd: number | null
  }) => {
    previousSelection.inserting = props.inserting
    previousSelection.start = props.originalStart
    previousSelection.end = props.originalEnd
    const start = props.start
    const end = props.end
    if(start === null || end === null) {
      setActiveIndexStart(-1)
      setActiveIndexEnd(-1)
      return
    }

    setActiveIndexStart(start)
    setActiveIndexEnd(end)
  }

  const onSelectionChange = (inputType?: string) => {
    if(
      !isFocused ||
      inputRef.ownerDocument.activeElement !== inputRef ||
      inputRef.selectionStart === null ||
      inputRef.selectionEnd === null
    ) {
      syncSelection({
        start: null,
        end: null,
        inserting: false,
        originalStart: inputRef.selectionStart,
        originalEnd: inputRef.selectionEnd
      })
      setIsInserting(false)
      return
    }
    const maxLength = props.length
    const inserting =
      inputRef.value.length < maxLength &&
      inputRef.selectionStart === inputRef.value.length
    setIsInserting(inserting)

    if(inserting || inputRef.selectionStart !== inputRef.selectionEnd) {
      syncSelection({
        start: inputRef.selectionStart,
        end: inserting ? inputRef.selectionEnd + 1 : inputRef.selectionEnd,
        inserting: inserting,
        originalStart: inputRef.selectionStart,
        originalEnd: inputRef.selectionEnd
      })
      return
    }

    let selectionStart = 0
    let selectionEnd = 0
    let direction: 'forward' | 'backward' | undefined = undefined
    if(inputRef.selectionStart === 0) {
      selectionStart = 0
      selectionEnd = 1
      direction = 'forward'
    } else if(inputRef.selectionStart === maxLength) {
      selectionStart = maxLength - 1
      selectionEnd = maxLength
      direction = 'backward'
    } else {
      let startOffset = 0
      let endOffset = 1
      if(previousSelection.start !== null && previousSelection.end !== null) {
        const navigatedBackwards =
          inputRef.selectionStart < previousSelection.end &&
          Math.abs(previousSelection.start - previousSelection.end) === 1
        direction = navigatedBackwards ? 'backward' : 'forward'
        if(
          (navigatedBackwards &&
            !previousSelection.inserting &&
            inputType !== 'deleteContentForward') ||
          (!navigatedBackwards && isShiftKeyDown)
        ) {
          startOffset += -1
        }
      }
      if(isShiftKeyDown && inputType === undefined) {
        endOffset += 1
      }
      selectionStart = inputRef.selectionStart + startOffset
      selectionEnd = inputRef.selectionEnd + startOffset + endOffset
    }

    inputRef.setSelectionRange(selectionStart, selectionEnd, direction)
    syncSelection({
      start: selectionStart,
      end: selectionEnd,
      inserting: inserting,
      originalStart: inputRef.selectionStart,
      originalEnd: inputRef.selectionEnd
    })
  }

  subscribeOn(document)('selectionchange', () => onSelectionChange())

  let inputRef!: HTMLInputElement;
  // a code reads left to right in any language: the first digit is in the leftmost box
  return (
    <div
      dir="ltr"
      class={classNames(
        styles.wrap,
        props.error && styles.error,
        props.disabled && styles.disabled,
        props.class
      )}>
      <input
        ref={(el) => {
          inputRef = el;
          (props.ref as any)?.(el);
        }}
        class={styles.input}
        aria-label={I18n.format(props.label || 'AccDescr.LoginCode', true)}
        inputmode="numeric"
        autocomplete="one-time-code"
        required
        spellcheck={false}
        pattern="^\d*$"
        value={value()}
        disabled={props.disabled}
        onFocus={() => {
          inputRef.setSelectionRange(value().length, value().length);
          isFocused = true;
          onSelectionChange()
        }}
        onBlur={() => {
          isFocused = false;
          onSelectionChange()
        }}
        onKeyDown={(e) => {
          if(e.key === 'Shift') {
            isShiftKeyDown = true;
          }
        }}
        onKeyUp={(e) => {
          if(e.key === 'Shift') {
            isShiftKeyDown = false;
          }
        }}
        onInput={(e) => {
          const rawValue = inputRef.value
          let finalValue = rawValue
          const oldValue = value()
          const selectionSize = Math.abs(
            (previousSelection.start ?? 0) - (previousSelection.end ?? 0)
          )

          if((previousSelection.inserting || selectionSize === oldValue.length)) {
            finalValue = finalValue.replace(/[^\d]/g, '')
          }
          finalValue = finalValue.slice(0, props.length)

          const hasInvalidChars = !/^\d*$/.test(finalValue)
          if(
            (rawValue.length !== 0 && finalValue.length === 0) ||
            finalValue === oldValue ||
            hasInvalidChars
          ) {
            e.preventDefault()
            e.currentTarget.value = oldValue
            if(hasInvalidChars) {
              e.currentTarget.setSelectionRange(
                previousSelection.start ?? 0,
                previousSelection.end ?? 0
              )
            }
            return
          }

          if(finalValue.length < oldValue.length) {
            onSelectionChange(e.inputType)
          }

          setValue(finalValue)

          props.onChange?.(finalValue)
          if(finalValue.length === props.length) {
            props.onFill?.(finalValue)
          }
        }}
      />

      <Index each={Array.from({length: props.length})}>
        {(_, idx) => (
          <div
            class={classNames(
              styles.digit,
              (activeIndexStart() <= idx && idx < activeIndexEnd()) && styles.active
            )}
          >
            <Transition>
              <Show when={value()[idx]}>
                <div class={styles.digitContent}>
                  {value()[idx]}
                </div>
              </Show>
            </Transition>
            {isInserting() && value().length === idx && <div class={styles.caret} />}
          </div>
        )}
      </Index>
    </div>
  )
}
