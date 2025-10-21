<script setup lang="ts">
import Game from '@/components/Game.vue'
import Leaderboard from '@/components/Leaderboard.vue'
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'

const reloadLeaderBoard = ref<boolean>(false)
</script>

<template>
  <Head title="Play now!" />

  <header class="fixed inset-x-0 top-0 z-50 border-b border-gray-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-900/80">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
      <a href="/" class="flex items-center gap-2">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 font-semibold text-white">Ty</span>
        <span class="text-sm font-semibold text-gray-900 sm:text-base dark:text-gray-100">Typi</span>
      </a>
    </div>
  </header>

  <div class="min-h-screen bg-gray-50 pt-16 dark:bg-slate-950">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
      <div class="flex flex-col gap-6 md:flex-row">
        <main class="flex-1">
          <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-gray-100">
            <Game @reload-scoreboard="reloadLeaderBoard = true" />
          </div>
        </main>

        <aside class="w-full md:w-72 md:flex-none">
          <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-gray-100">
            <Leaderboard v-model:reload="reloadLeaderBoard" />
          </div>
        </aside>
      </div>
    </div>
  </div>
</template>
