import { NextResponse } from 'next/server';
import { handleUpload, type HandleUploadBody } from '@vercel/blob/client';
import { currentUser } from '@/lib/auth';
import { ALLOWED_TYPES, MAX_BYTES } from '@/lib/storage';

/** Issues upload tokens so administrators can upload 360° photos straight from the browser to Vercel Blob. */
export async function POST(request: Request) {
  const body = (await request.json()) as HandleUploadBody;
  try {
    const result = await handleUpload({
      body, request,
      onBeforeGenerateToken: async () => {
        const u = await currentUser();
        if (!u || u.role !== 'admin') throw new Error('Only administrators can upload images.');
        return { allowedContentTypes: ALLOWED_TYPES, maximumSizeInBytes: MAX_BYTES, addRandomSuffix: true };
      },
      onUploadCompleted: async () => {},
    });
    return NextResponse.json(result);
  } catch (e) {
    return NextResponse.json({ error: e instanceof Error ? e.message : 'Upload failed' }, { status: 400 });
  }
}
