<script lang="ts" setup>
interface Props {
  maxInputValue?: number;
  maxlength?: number;
}

const props = withDefaults(defineProps<Props>(), {
  maxlength: 4,
})

const count = defineModel<number | string>( { default: 0 })

function handleFocus(e: any) {
  if (e.target.value === '' || e.target.value == '0') {
    e.target.value = ''
  } else {
    e.target.value = count.value
  }
}
function handleFocusOut(e: any) {
  if (e.target.value === '' || e.target.value == '0' || e.target.value === undefined || e.target.value === null) {
    e.target.value = 0
    count.value = 0
  } else {
    e.target.value = count.value
  }
}
function handleInputKeys(e: KeyboardEvent): boolean {
  const isValidKey = e.code === 'Backspace' || (e.code.startsWith('Digit') && e.key >= '0' && e.key <= '9');
  return isValidKey;
}

function handleInputValue(e: Event): void {
  if (props.maxInputValue === 0) {
    count.value = 0;
  }
  else if (props.maxInputValue && Number(count.value) > props.maxInputValue) {
    count.value = props.maxInputValue;
  }
}
</script>

<template>
  <input
    class="border border-solid outline-none bg-primary border-primary-light focus:border-primary-light focus:ring-primary-light inline-flex w-16 text-center items-center justify-center text-light disabled:opacity-50 disabled:shadow-none disabled:pointer-events-none"
    type="text" min="0" :max="maxInputValue" inputmode="numeric" pattern="[0-9]*" :maxlength="maxlength" v-model.number="count"
    @focus="handleFocus" @blur="handleFocusOut" @input="handleInputValue" :onkeypress="handleInputKeys" :disabled="maxInputValue == 0">
</template>

<style scoped>
:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}
</style>
