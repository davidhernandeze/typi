<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'

type LeaderboardRow = { name: string; score: number }

const loading = ref(true)
const error = ref<string | null>(null)
const rows = ref<LeaderboardRow[]>([])
const reload = defineModel<boolean>('reload', { default: false })

onMounted(() => {
  void fetchLeaderboard()
})

watch(reload, () => {
  void fetchLeaderboard()
  reload.value = false
})

async function fetchLeaderboard() {
  loading.value = true
  error.value = null
  try {
    const res = await fetch('/api/leaderboard', {
      headers: { Accept: 'application/json' },
    })
    if (!res.ok) throw new Error(`Failed to load leaderboard (HTTP ${res.status})`)
    const json = (await res.json()) as { data: LeaderboardRow[] }
    rows.value = json.data ?? []
  } catch (e: any) {
    error.value = e?.message ?? 'Failed to load leaderboard'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <h3 class="mb-2 text-lg font-semibold">Leaderboard</h3>
    <div v-if="loading" class="text-sm text-gray-500">Loading…</div>
    <div v-else-if="error" class="text-sm text-red-600">{{ error }}</div>
    <ol v-else class="space-y-1">
      <li v-if="rows.length === 0" class="text-sm text-gray-500">No scores yet</li>
      <li v-for="(row, idx) in rows" :key="idx" class="flex items-center justify-between text-sm">
        <span class="truncate">{{ idx + 1 }}. {{ row.name || 'Anonymous' }}</span>
        <span class="font-mono tabular-nums">{{ row.score.toFixed(2) }}</span>
      </li>
    </ol>
  </div>
</template>

<style scoped></style>
