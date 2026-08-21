/**
 * The two settings tables share these, so a column reads the same on both.
 *
 * Tables scroll sideways rather than squashing their columns, which keeps a long
 * submission error readable instead of clipped by the card: the table holds a
 * minimum width and the wrapper takes the overflow.
 */
export const table = {
  wrap: 'overflow-x-auto',
  table: 'w-full min-w-[44rem] text-left text-sm',
  th: 'py-2 pr-4 text-xs font-medium tracking-wide text-gray-500 uppercase whitespace-nowrap border-b border-gray-200',
  td: 'py-2 pr-4 align-top border-b border-gray-100',
  // Every column but one keeps its own width, so the variable-length column
  // takes what is left.
  compact: 'whitespace-nowrap',
}
