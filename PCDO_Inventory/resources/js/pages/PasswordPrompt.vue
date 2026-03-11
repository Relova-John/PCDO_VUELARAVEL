<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  password: '',
});

</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
      <h2 class="text-2xl font-bold mb-4">Enter Password</h2>

      <form @submit.prevent="form.post('/password-prompt')">
        <input
          v-model="form.password"
          type="password"
          placeholder="Password"
          class="border p-2 w-full mb-4 rounded"
        />

        <button
          type="submit"
          class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
          :disabled="form.processing"
        >
          Submit
        </button>

        <p v-if="form.errors.password" class="text-red-500 mt-2">
          {{ form.errors.password }}
        </p>
      </form>
    </div>
  </div>
</template>