// The MadelineProto mark (src/WebUI/templates/assets/mark.svg), where tweb draws Telegram's logo: tinted
// with the accent, like tweb's.

export const LOGO_PATH = 'M9 22.5V10l7 7.5 7-7.5v12.5';

export default function Logo(props: {class?: string}) {
  return (
    <svg class={props.class} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" aria-hidden="true">
      <rect width="32" height="32" rx="9" style={{fill: 'var(--primary-color)'}} />
      <path d={LOGO_PATH} fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
  );
}

/**
 * The mark as an SVG document, for the middle of the QR code: on the colour of the code's background,
 * padded like tweb's `logo_padded.svg` so the code keeps its error correction.
 */
export function logoSvgText(color: string) {
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="-6 -6 44 44"><rect width="32" height="32" rx="9" fill="${color}"/><path d="${LOGO_PATH}" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
}
