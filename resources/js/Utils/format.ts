export function numberFormat(number: number) {
  return new Intl.NumberFormat('de-DE').format(number)
}

export function timeFormat(numberInSeconds: number) {
  const seconds = numberInSeconds % 60
  let minutes = Math.floor(numberInSeconds / 60)
  const hours = Math.floor(minutes / 60)

  let formattedTime = `${timeFormatToDoubleDigit(minutes)}:${timeFormatToDoubleDigit(seconds)}`

  if (hours >= 1) {
    minutes = minutes % 60
    formattedTime = `${timeFormatToDoubleDigit(hours)}:${timeFormatToDoubleDigit(minutes)}:${timeFormatToDoubleDigit(seconds)}`
  }

  return formattedTime
}

// if timeNumber is a single digit like 10:0
// convert timeNumber to double digit like 10:00
function timeFormatToDoubleDigit(timeNumber: number) {
  return timeNumber.toString().padStart(2, '0')
}
