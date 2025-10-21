import { clsx, type ClassValue } from 'clsx'
import { twMerge } from 'tailwind-merge'

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export function csrfToken() {
  const el = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null
  return el?.content
}
