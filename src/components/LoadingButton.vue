<!-- <template>
    <button
      :type="type"
      class="btn w-100 d-flex justify-content-center align-items-center"
      :class="btnClass"
      :disabled="disabled || loading"
    >
      <span v-if="loading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
      <span v-else>{{ label }}</span>
    </button>
  </template>
  
  <script setup>
  defineProps({
    label: { type: String, required: true },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    btnClass: { type: String, default: 'btn-primary' },
    type: { type: String, default: 'submit' }
  })
  </script>
   -->
<template>
  <button
    :type="type"
    class="btn d-flex align-items-center"
    :class="[
      btnClass,
      small ? 'btn-sm w-auto justify-content-end ms-auto' : 'w-100 justify-content-center'
    ]"
    :disabled="disabled || loading"
  >
    <span
      v-if="loading"
      class="spinner-border spinner-border-sm me-1"
      role="status"
      aria-hidden="true"
    ></span>
    <span>{{ label }}</span>
  </button>
</template>



<script setup>
defineProps({
  label: { type: String, required: true },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  btnClass: { type: String, default: 'btn-primary' },
  type: { type: String, default: 'submit' },
  small: { type: Boolean, default: false } // NEW prop
})
</script>
