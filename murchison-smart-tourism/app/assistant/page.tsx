import type { Metadata } from 'next';
import Link from 'next/link';
import Chat from '@/components/Chat';

export const metadata: Metadata = { title: 'AI travel assistant' };

export default function AssistantPage() {
  return (
    <>
      <section className="page-head">
        <div className="container">
          <div className="eyebrow">AI travel assistant</div>
          <h1>Ask anything about your visit</h1>
          <p>The assistant answers questions about Murchison Falls National Park and recommends activities that suit you.</p>
        </div>
      </section>
      <section className="section">
        <div className="container grid chat-grid">
          <Chat />
          <div className="card">
            <h3>Good to know</h3>
            <ul className="clean small">
              <li>Answers use the park information in this system. Prices are estimates — confirm with UWA.</li>
              <li>For a complete timed plan, use the <Link href="/plan">Smart itinerary</Link>.</li>
              <li>For costs, use the <Link href="/budget">Travel budget</Link>; for the forecast, see <Link href="/updates">Travel updates</Link>.</li>
              <li>Do not share passwords or payment details in the chat.</li>
            </ul>
          </div>
        </div>
      </section>
    </>
  );
}
