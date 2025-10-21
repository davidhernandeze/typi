<script setup lang="ts">
import { csrfToken } from '@/lib/utils'
import { computed, ref, watch } from 'vue'

type KeyEvent = { key: string; ts: number }

const props = defineProps<{
  show: boolean
  sentenceId: number | undefined
  events: KeyEvent[]
  startedAt: number | null
  finishedAt: number | null
}>()

const emit = defineEmits<{
  (e: 'playAgain'): void
}>()

const playerName = ref<string>(localStorage.getItem('player_name') ?? '')
watch(playerName, (val) => {
  try {
    localStorage.setItem('player_name', val ?? '')
  } catch (e) {
    void e
  }
})

const submitting = ref(false)
const submitError = ref<string | null>(null)

const processing = ref(false)
const processError = ref<string | null>(null)
type ProcessResponse = { score: { id: number; words_per_minute: number; accuracy_percentage: number; time_taken: number }; new_high_score: boolean }
const processResult = ref<ProcessResponse | null>(null)

const durationMs = computed(() => {
  return props.startedAt && props.finishedAt ? props.finishedAt - props.startedAt : null
})

async function postProcess() {
  if (processing.value || processResult.value || !props.show) return
  if (!props.sentenceId || !props.startedAt || !props.finishedAt || !durationMs.value) return

  processing.value = true
  processError.value = null
  try {
    const payload = {
      sentence_id: props.sentenceId,
      events: props.events ?? [],
      started_at: props.startedAt,
      finished_at: props.finishedAt,
      duration_ms: durationMs.value,
    }
    const res = await fetch('/process', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken() ?? '',
        Accept: 'application/json',
      },
      body: JSON.stringify(payload),
    })
    if (!res.ok) {
      processError.value = `Failed to process (HTTP ${res.status})`
    } else {
      processResult.value = (await res.json()) as any
    }
  } catch (err: any) {
    processError.value = err?.message ?? 'Failed to process score'
  } finally {
    processing.value = false
  }
}

watch(
  () => props.show,
  (show) => {
    if (show) {
      void postProcess()
    } else {
      processing.value = false
      processError.value = null
      processResult.value = null
      submitting.value = false
      submitError.value = null
    }
  },
)

async function handleSubmitAndPlayAgain() {
  submitting.value = true
  submitError.value = null
  try {
    const res = await fetch('/score', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken() ?? '',
        Accept: 'application/json',
      },
      body: JSON.stringify({ name: playerName.value || 'Anonymous' }),
    })
    if (!res.ok) {
      submitError.value = `Failed to submit score (HTTP ${res.status})`
    }
  } catch (err: any) {
    submitError.value = err?.message ?? 'Failed to submit score'
  } finally {
    submitting.value = false
    emit('playAgain')
  }
}
</script>

<template>
  <div v-if="props.show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="w-full max-w-md rounded-lg p-6 shadow-lg dark:bg-slate-900">
      <h2 class="mb-2 text-xl font-semibold">Congratulations! 🎉</h2>
      <p class="mb-4">You finished the sentence.</p>

      <div v-if="processResult?.new_high_score" class="mb-4">
        <label for="playerName" class="mb-1 block text-sm font-medium">Your name</label>
        <input
          id="playerName"
          v-model="playerName"
          type="text"
          class="w-full rounded border px-3 py-2 outline-none focus:border-blue-400 focus:ring"
          placeholder="Enter your name"
          autocomplete="off"
          autocapitalize="off"
          spellcheck="false"
        />
      </div>

      <div class="mb-4 text-sm">
        <div v-if="processing">Processing your result…</div>
        <div v-else-if="processError" class="text-red-600">{{ processError }}</div>
        <div v-else-if="processResult" class="space-y-1">
          <div class="flex items-center justify-between">
            <div><span class="font-medium">WPM:</span> {{ processResult.score.words_per_minute }}</div>
            <div v-if="processResult.new_high_score" class="ml-2 rounded bg-yellow-100 px-2 py-0.5 text-xs font-semibold text-yellow-800">
              New high score!
            </div>
          </div>
          <div><span class="font-medium">Accuracy:</span> {{ processResult.score.accuracy_percentage }}%</div>
          <div><span class="font-medium">Time:</span> {{ (processResult.score.time_taken / 1000).toFixed(2) }}s</div>
          <div v-if="!processResult.new_high_score" class="text-xs text-gray-500">
            Not your highest WPM this session. You can still play again to improve it.
          </div>
        </div>
      </div>

      <div v-if="submitError" class="mt-2 text-sm text-red-600">{{ submitError }}</div>

      <div class="flex justify-end gap-2">
        <button class="rounded border px-3 py-2" :disabled="processing || submitting" @click="emit('playAgain')">Play again</button>
        <button
          v-if="processResult && processResult.new_high_score"
          class="rounded bg-blue-600 px-3 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
          :disabled="submitting || processing"
          @click="handleSubmitAndPlayAgain"
        >
          {{ submitting ? 'Submitting…' : 'Submit and play again' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped></style>
