import { NextResponse } from 'next/server';
import { all } from '@/lib/db';

export const dynamic = 'force-dynamic';

/** Published 360° scenes, used by the itinerary to link activities to their 360° views. */
export async function GET() {
  const rows = await all<{ id: number; title: string; activity_id: string | null }>('SELECT id, title, activity_id FROM scenes WHERE published = 1 ORDER BY sort, id').catch(() => []);
  return NextResponse.json(rows);
}
