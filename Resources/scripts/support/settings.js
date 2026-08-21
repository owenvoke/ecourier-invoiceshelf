/**
 * The settings endpoint returns whatever `CompanySetting` stored, which for a
 * checkbox is the string `'1'` or `'0'` rather than a boolean.
 */
export function asBoolean(value, fallback = false) {
  if (value === undefined || value === null || value === '') {
    return fallback
  }

  return ['1', 'true', 'yes', true].includes(value)
}
