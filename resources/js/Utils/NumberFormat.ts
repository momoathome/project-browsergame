export function numberFormat(number: number) {
  return new Intl.NumberFormat('de-DE').format(number)
}
