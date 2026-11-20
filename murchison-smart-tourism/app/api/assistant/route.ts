import Anthropic from '@anthropic-ai/sdk';
import { NextResponse } from 'next/server';
import { guideAnswer, parkKnowledge } from '@/lib/assistant';

export const runtime = 'nodejs';
export const maxDuration = 60;

type ChatMessage = { role: 'user' | 'assistant'; content: string };

const SYSTEM = `You are the travel assistant of the Murchison Falls Smart Tourism Platform, a trip-planning website for Murchison Falls National Park in Uganda.
Answer visitors' questions about the park and recommend activities that suit them. Keep answers short and practical (under about 150 words), in plain English, using short paragraphs or bullet points.
Base prices, distances and times only on the park information below. Say that fees are estimates to be confirmed with the Uganda Wildlife Authority (UWA). If you do not know something, say so and suggest contacting UWA.
When a visitor wants a full plan, cost estimate, map or forecast, point them to the Smart itinerary, Travel budget, Map & routes or Travel updates pages of this website.
Only help with travel to Murchison Falls and Uganda; politely decline unrelated requests.

PARK INFORMATION
${parkKnowledge()}`;

export async function POST(req: Request) {
  let messages: ChatMessage[] = [];
  try {
    const body = await req.json();
    messages = (Array.isArray(body?.messages) ? body.messages : [])
      .filter((m: ChatMessage) => (m?.role === 'user' || m?.role === 'assistant') && typeof m.content === 'string' && m.content.trim())
      .slice(-12)
      .map((m: ChatMessage) => ({ role: m.role, content: m.content.slice(0, 2000) }));
  } catch {
    return NextResponse.json({ error: 'Invalid request' }, { status: 400 });
  }
  const last = messages[messages.length - 1];
  if (!last || last.role !== 'user') return NextResponse.json({ error: 'Send a question' }, { status: 400 });

  // Without a key, answer from the park data so the assistant always works.
  if (!process.env.ANTHROPIC_API_KEY) return NextResponse.json({ reply: guideAnswer(last.content), source: 'guide' });

  // The conversation must start with a user turn.
  while (messages.length && messages[0].role !== 'user') messages.shift();

  try {
    const client = new Anthropic();
    const response = await client.beta.messages.create({
      model: 'claude-opus-5-5',
      max_tokens: 4000,
      betas: ['server-side-fallback-2026-07-01'],
      fallbacks: 'default',
      output_config: { effort: 'low' },
      system: [{ type: 'text', text: SYSTEM, cache_control: { type: 'ephemeral' } }],
      messages,
    });
    if (response.stop_reason === 'refusal') return NextResponse.json({ reply: guideAnswer(last.content), source: 'guide' });
    const reply = response.content.flatMap(b => (b.type === 'text' ? [b.text] : [])).join('\n').trim();
    return NextResponse.json({ reply: reply || guideAnswer(last.content), source: reply ? 'ai' : 'guide' });
  } catch (err) {
    if (err instanceof Anthropic.RateLimitError) console.warn('Assistant rate limited; using the built-in guide');
    else if (err instanceof Anthropic.APIError) console.error('Assistant API error', err.status, err.message);
    else console.error('Assistant error', err);
    return NextResponse.json({ reply: guideAnswer(last.content), source: 'guide' });
  }
}
