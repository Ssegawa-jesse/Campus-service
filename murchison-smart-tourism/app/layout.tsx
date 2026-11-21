import type { Metadata, Viewport } from 'next';
import './globals.css';
import Nav from '@/components/Nav';
import { currentUser } from '@/lib/auth';
import { PARK } from '@/lib/data';

export const metadata: Metadata = {
  title: { default: 'Murchison Falls Smart Tourism', template: '%s | Murchison Falls Smart Tourism' },
  description: 'Plan a personalised trip to Murchison Falls National Park: smart itineraries, budgets, maps, 360° exploration, an AI travel assistant and weather updates.',
};
export const viewport: Viewport = { width: 'device-width', initialScale: 1, themeColor: '#1f4d3a' };

export default async function RootLayout({ children }: { children: React.ReactNode }) {
  const user = await currentUser().catch(() => null);
  return (
    <html lang="en">
      <body>
        <Nav user={user ? { name: user.name, role: user.role } : null} />
        <main>{children}</main>
        <footer className="footer">
          <div className="container grid">
            <div>
              <strong style={{ color: '#fff' }}>Murchison Falls Smart Tourism Platform</strong>
              <p className="small" style={{ marginTop: 6 }}>
                A student project (MUBS, Bachelor of Business Computing) for {PARK.name}, managed by the {PARK.manager}.
                Fees and times are planning estimates — confirm with UWA before travelling.
              </p>
            </div>
            <div>
              <strong style={{ color: '#fff' }}>Plan</strong>
              <div className="small"><a href="/plan">Smart itinerary</a><br /><a href="/budget">Travel budget</a><br /><a href="/operators">Tour operators</a><br /><a href="/register?role=operator">Register as an operator</a></div>
            </div>
            <div>
              <strong style={{ color: '#fff' }}>Contact</strong>
              <div className="small">{PARK.contact.email}<br />{PARK.contact.phone}</div>
            </div>
          </div>
        </footer>
      </body>
    </html>
  );
}
