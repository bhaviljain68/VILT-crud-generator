<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue'
import { Link } from '@inertiajs/vue3'

defineProps<{
    route: string
}>()
</script>

<template>
    <Button asChild class="inline-block flex justify-start items-center gap-1 max-w-max mb-8" variant="default">
        <Link :href="route">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22">
            <path fill="currentColor" d="M15.41 16.58L10.83 12l4.58-4.59L14 6l-6 6l6 6z" />
        </svg>
        Back
        </Link>
    </Button>
</template>
