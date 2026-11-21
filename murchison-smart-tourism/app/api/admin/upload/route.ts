import { NextResponse } from 'next/server';
import { currentUser } from '@/lib/auth';
import { blobEnabled, saveLocalUpload } from '@/lib/storage';

/** Uploads a 360° photo to the project's "uploads" folder (used when running on your own computer). */
export async function POST(req: Request) {
  const u = await currentUser();
  if (!u || u.role !== 'admin') return NextResponse.json({ error: 'Only administrators can upload images.' }, { status: 403 });
  if (blobEnabled() || process.env.VERCEL) return NextResponse.json({ error: 'On Vercel, images are uploaded to Vercel Blob.' }, { status: 400 });
  const file = (await req.formData()).get('file');
  if (!(file instanceof File)) return NextResponse.json({ error: 'Choose an image.' }, { status: 400 });
  try {
    return NextResponse.json({ url: await saveLocalUpload(file) });
  } catch (e) {
    return NextResponse.json({ error: e instanceof Error ? e.message : 'Upload failed' }, { status: 400 });
  }
}
