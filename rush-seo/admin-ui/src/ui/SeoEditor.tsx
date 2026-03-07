import React, { useEffect, useMemo, useState } from 'react'

type Annotation = {
  severity: 'red' | 'yellow'
  range: { start: number; end: number }
  message: string
  suggestion?: string
}

export function SeoEditor() {
  const [text, setText] = useState<string>('This is a sample product description. It might be long long long long long long long long long long long long long long long long long long long long.')
  const [focus, setFocus] = useState<string>('sample')
  const [annotations, setAnnotations] = useState<Annotation[]>([])

  useEffect(() => {
    const controller = new AbortController()
    async function analyze() {
      const res = await fetch('/wp-json/rush-seo/v1/editor/analyze', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ text, focus_keyword: focus }),
        signal: controller.signal
      })
      const data = await res.json()
      if (data?.annotations) setAnnotations(data.annotations)
    }
    analyze().catch(() => {})
    return () => controller.abort()
  }, [text, focus])

  const decorated = useMemo(() => decorateText(text, annotations), [text, annotations])

  return (
    <div>
      <div style={{ marginBottom: 8 }}>
        <input value={focus} onChange={e => setFocus(e.target.value)} placeholder="Focus keyword" style={{ padding: 8, width: '100%', boxSizing: 'border-box' }} />
      </div>
      <textarea value={text} onChange={e => setText(e.target.value)} rows={5} style={{ width: '100%', padding: 8, boxSizing: 'border-box', marginBottom: 12 }} />
      <div>
        <div dangerouslySetInnerHTML={{ __html: decorated }} />
      </div>
    </div>
  )
}

function decorateText(text: string, annotations: Annotation[]): string {
  const segments: { start: number; end: number; classes: string; title: string }[] = []
  for (const a of annotations) {
    const cls = a.severity === 'red' ? 'rush-red' : 'rush-yellow'
    segments.push({ start: a.range.start, end: a.range.end, classes: cls, title: a.message + (a.suggestion ? `\n${a.suggestion}` : '') })
  }
  segments.sort((x, y) => x.start - y.start)
  let html = ''
  let idx = 0
  for (const seg of segments) {
    const safeStart = Math.max(0, Math.min(seg.start, text.length))
    const safeEnd = Math.max(safeStart, Math.min(seg.end || seg.start + 1, text.length))
    html += escapeHtml(text.slice(idx, safeStart))
    const frag = text.slice(safeStart, safeEnd)
    html += `<span class="${seg.classes}" title="${escapeHtml(seg.title)}">${escapeHtml(frag)}</span>`
    idx = safeEnd
  }
  html += escapeHtml(text.slice(idx))
  const styles = `<style>.rush-red{ text-decoration: underline; text-decoration-color: #d82c0d; text-decoration-thickness: 2px; text-decoration-style: wavy; }
.rush-yellow{ text-decoration: underline; text-decoration-color: #eec200; text-decoration-thickness: 2px; text-decoration-style: wavy; }</style>`
  return styles + `<div style="white-space: pre-wrap; font-family: inherit;">${html}</div>`
}

function escapeHtml(s: string): string {
  return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;')
}

