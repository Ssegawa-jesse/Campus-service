'use client';
import { useEffect, useRef, useState } from 'react';

type Msg = { role: 'user' | 'assistant'; content: string; source?: 'ai' | 'guide' };

const SUGGESTIONS = [
  'How many days do I need?',
  'How much is the boat trip to the falls?',
  'When is the best time to visit?',
  'Where can I see the shoebill?',
  'Can children track chimpanzees?',
  'What should I pack?',
];

export default function Chat() {
  const [messages, setMessages] = useState<Msg[]>([
    { role: 'assistant', content: 'Hello! I am your Murchison Falls travel assistant. Ask me about fees, activities, the best time to visit, getting there or what to pack.' },
  ]);
  const [text, setText] = useState('');
  const [busy, setBusy] = useState(false);
  const log = useRef<HTMLDivElement>(null);

  useEffect(() => { log.current?.scrollTo({ top: log.current.scrollHeight, behavior: 'smooth' }); }, [messages, busy]);

  async function send(question: string) {
    const q = question.trim();
    if (!q || busy) return;
    const next: Msg[] = [...messages, { role: 'user', content: q }];
    setMessages(next);
    setText('');
    setBusy(true);
    try {
      const res = await fetch('/api/assistant', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        // The greeting is not sent: the conversation sent to the server starts with the visitor's first question.
        body: JSON.stringify({ messages: next.slice(1).map(m => ({ role: m.role, content: m.content })) }),
      });
      const data = await res.json();
      setMessages([...next, { role: 'assistant', content: data.reply ?? data.error ?? 'Sorry, something went wrong.', source: data.source }]);
    } catch {
      setMessages([...next, { role: 'assistant', content: 'Sorry, I could not reach the server. Check your connection and try again.' }]);
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="card chat">
      <div className="chat-log" ref={log} aria-live="polite">
        {messages.map((m, i) => (
          <div key={i} className={`msg ${m.role === 'user' ? 'user' : 'bot'}`}>
            {m.content}
            {m.source && <span className="src">{m.source === 'ai' ? 'AI answer' : 'Answer from the park guide'}</span>}
          </div>
        ))}
        {busy && <div className="msg bot muted">Thinking…</div>}
      </div>
      <div className="suggestions">
        {SUGGESTIONS.map(s => <button key={s} type="button" onClick={() => send(s)} disabled={busy}>{s}</button>)}
      </div>
      <form className="chat-form" onSubmit={e => { e.preventDefault(); send(text); }}>
        <input type="text" value={text} onChange={e => setText(e.target.value)} placeholder="Ask about Murchison Falls…" aria-label="Your question" maxLength={500} />
        <button className="btn btn-primary" disabled={busy || !text.trim()}>Send</button>
      </form>
    </div>
  );
}
