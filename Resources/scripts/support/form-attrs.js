/**
 * Attributes that keep browsers and password managers away from these forms.
 *
 * The settings and recipient fields are configuration — API keys, participant
 * identifiers, bank details — not credentials a manager should offer to fill or
 * save. `autocomplete="off"` covers the browser; `data-1p-ignore` is
 * 1Password's documented opt-out, which it honours in places where it ignores
 * `autocomplete` altogether.
 */
export const noAutofill = {
  autocomplete: 'off',
  'data-1p-ignore': 'true',
}
