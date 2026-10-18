// tweb's day/night switch, without its themeController: the palettes are static (scss/theme.scss), and
// `.night` on <html> picks the night one. It follows the system until the user picks one.

const STORAGE_KEY = 'madeline-webui-night';

function stored(): boolean | undefined {
  try {
    const value = window.localStorage.getItem(STORAGE_KEY);
    return value === null ? undefined : value === '1';
  } catch(err) {
    return undefined;
  }
}

function prefersNight() {
  return !!window.matchMedia?.('(prefers-color-scheme: dark)').matches;
}

export function isNight() {
  return document.documentElement.classList.contains('night');
}

export function setNight(night: boolean) {
  document.documentElement.classList.toggle('night', night);
  window.dispatchEvent(new CustomEvent('madeline-theme-change'));
}

export function toggleNight() {
  const night = !isNight();
  setNight(night);
  try {
    window.localStorage.setItem(STORAGE_KEY, night ? '1' : '0');
  } catch(err) {}
}

/** Apply the theme the user picked, or the system's, and follow the system's until they pick one. */
export function initTheme() {
  setNight(stored() ?? prefersNight());
  window.matchMedia?.('(prefers-color-scheme: dark)').addEventListener?.('change', (e) => {
    if(stored() === undefined) setNight(e.matches);
  });
}
