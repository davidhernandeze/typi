<script setup lang="ts">
import { KeyEvent, Sentence } from '@/types'
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import CongratsModal from './CongratsModal.vue'

const emit = defineEmits<{
  (e: 'reloadScoreboard'): void
}>()

const loading = ref(true)
const error = ref<string | null>(null)
const sentence = ref<Sentence | null>(null)
const words = ref<string[]>([])
const currentWordIndex = ref(0)
const inputValue = ref('')
const correctCount = ref(0)

const containerRef = ref<HTMLElement | null>(null)
const wordRefs = ref<HTMLElement[]>([])

const keyEvents = ref<KeyEvent[]>([])
const startedAt = ref<number | null>(null)
const finishedAt = ref<number | null>(null)
const showWinModal = ref(false)

const progress = computed(() => (words.value.length ? Math.round((correctCount.value / words.value.length) * 100) : 0))

const finished = computed(() => currentWordIndex.value >= words.value.length)

async function fetchSentence() {
  loading.value = true
  error.value = null
  try {
    const res = await fetch('/api/sentence')
    const data: Sentence = await res.json()
    sentence.value = data
    words.value = data.text.trim().split(/\s+/)
    currentWordIndex.value = 0
    inputValue.value = ''
    correctCount.value = 0
    keyEvents.value = []
    startedAt.value = null
    finishedAt.value = null
    showWinModal.value = false
    await nextTick()
    scrollActiveIntoView()
  } catch (e: any) {
    error.value = e?.message ?? 'Failed to load sentence'
  }

  loading.value = false
}

function onKeydown(e: KeyboardEvent) {
  if (finished.value) {
    e.preventDefault()
    return
  }
  keyEvents.value.push({ key: e.key, ts: Date.now() })
  if (!startedAt.value) startedAt.value = Date.now()

  if (e.key === ' ') {
    e.preventDefault()
    const target = words.value[currentWordIndex.value] ?? ''
    if (inputValue.value === target) {
      correctCount.value++
      currentWordIndex.value++
      inputValue.value = ''
    }
  }
}

watch(inputValue, (val) => {
  if (finished.value) return
  if (!words.value.length) return
  const lastIndex = words.value.length - 1
  if (currentWordIndex.value !== lastIndex) return
  const target = words.value[currentWordIndex.value] ?? ''
  if (val === target) {
    correctCount.value++
    currentWordIndex.value++
  }
})

watch(currentWordIndex, async () => {
  await nextTick()
  scrollActiveIntoView()
})

watch(
  () => finished.value,
  async (isFinished) => {
    if (!isFinished) return
    finishedAt.value = Date.now()
    showWinModal.value = true
  },
)

async function playAgain() {
  showWinModal.value = false
  emit('reloadScoreboard')
  await fetchSentence()
}

function setWordRef(el: HTMLElement | null, index: number) {
  if (el) {
    wordRefs.value[index] = el
  }
}

function scrollActiveIntoView() {
  const container = containerRef.value
  const activeEl = wordRefs.value[currentWordIndex.value]
  if (!activeEl || !container) return

  const cRect = container.getBoundingClientRect()
  const aRect = activeEl.getBoundingClientRect()
  if (aRect.top < cRect.top) {
    activeEl.scrollIntoView({ block: 'nearest', inline: 'nearest' })
  } else if (aRect.bottom > cRect.bottom) {
    activeEl.scrollIntoView({ block: 'nearest', inline: 'nearest' })
  }
}

function correctPrefixLength(word: string, typed: string): number {
  const len = Math.min(word.length, typed.length)
  let i = 0
  for (; i < len; i++) {
    if (word[i] !== typed[i]) break
  }
  return i
}

function mismatchIndex(word: string, typed: string): number {
  const len = Math.min(word.length, typed.length)
  for (let i = 0; i < len; i++) {
    if (word[i] !== typed[i]) return i
  }
  if (typed.length > word.length) return word.length
  return -1
}

onMounted(fetchSentence)
</script>

<template>
  <div class="space-y-4">
    <div class="h-2 w-full rounded bg-gray-200">
      <div class="h-full rounded bg-green-500" :style="{ width: progress + '%' }"></div>
    </div>

    <div v-if="loading">Loading...</div>
    <div v-else-if="error" class="text-red-600">{{ error }}</div>

    <div v-else class="space-y-3">
      <div ref="containerRef" class="h-[25rem] overflow-y-auto rounded border border-gray-200 p-3 text-lg leading-7">
        <template v-for="(word, i) in words" :key="i">
          <span :ref="(el) => setWordRef(el as HTMLElement | null, i)" class="whitespace-pre-wrap">
            <template v-if="i < currentWordIndex">
              <span class="text-green-600">{{ word + ' ' }}</span>
            </template>
            <template v-else-if="i === currentWordIndex">
              <template v-if="inputValue.length">
                <template v-if="mismatchIndex(word, inputValue) >= 0 && mismatchIndex(word, inputValue) < word.length">
                  <span class="text-green-600">{{ word.slice(0, correctPrefixLength(word, inputValue)) }}</span
                  ><span class="text-red-600">{{ word.slice(correctPrefixLength(word, inputValue), correctPrefixLength(word, inputValue) + 1) }}</span
                  ><span>{{ word.slice(correctPrefixLength(word, inputValue) + 1) + ' ' }}</span>
                </template>
                <template v-else>
                  <span class="text-green-600">{{ word.slice(0, correctPrefixLength(word, inputValue)) }}</span
                  ><span>{{ word.slice(correctPrefixLength(word, inputValue)) + ' ' }}</span>
                </template>
              </template>
              <template v-else>
                {{ word + ' ' }}
              </template>
            </template>
            <template v-else>
              {{ word + ' ' }}
            </template>
          </span>
          <span v-if="i < words.length - 1"> </span>
        </template>
      </div>

      <div class="flex items-center gap-3">
        <input
          v-model="inputValue"
          @keydown="onKeydown"
          :disabled="finished"
          type="text"
          class="w-full rounded border px-3 py-2 outline-none focus:border-blue-400 focus:ring"
          placeholder="Type the current word, press Space to confirm"
          autocomplete="off"
          autocapitalize="off"
          spellcheck="false"
        />
      </div>

      <div>Completed {{ correctCount }} / {{ words.length }} words</div>
    </div>
  </div>
  <CongratsModal
    :show="showWinModal"
    :sentenceId="sentence?.id"
    :events="keyEvents"
    :startedAt="startedAt"
    :finishedAt="finishedAt"
    @playAgain="playAgain"
  />
</template>
