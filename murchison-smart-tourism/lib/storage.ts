import 'server-only';
import { mkdir, writeFile } from 'fs/promises';
import path from 'path';
import { randomBytes } from 'crypto';

/**
 * Where uploaded 360° photos are kept:
 * - On Vercel: Vercel Blob (set BLOB_READ_WRITE_TOKEN by connecting a Blob store to the project). Photos are uploaded
 *   straight from the browser to Blob, so large 360° images are not limited by Vercel's 4.5 MB request size.
 * - On your computer: the "uploads" folder inside the project, served by /api/uploads/<file>.
 */
export const blobEnabled = () => !!process.env.BLOB_READ_WRITE_TOKEN;
export const UPLOAD_DIR = path.join(process.cwd(), 'uploads');
export const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
export const MAX_BYTES = 25 * 1024 * 1024;

export async function saveLocalUpload(file: File): Promise<string> {
  if (!ALLOWED_TYPES.includes(file.type)) throw new Error('Upload a JPG, PNG or WebP image.');
  if (file.size > MAX_BYTES) throw new Error('The image is larger than 25 MB. Resize it to about 4096 × 2048 pixels.');
  const ext = file.type === 'image/png' ? 'png' : file.type === 'image/webp' ? 'webp' : 'jpg';
  const base = path.basename(file.name, path.extname(file.name)).toLowerCase().replace(/[^a-z0-9]+/g, '-').slice(0, 40) || 'scene';
  const name = `${base}-${randomBytes(4).toString('hex')}.${ext}`;
  await mkdir(UPLOAD_DIR, { recursive: true });
  await writeFile(path.join(UPLOAD_DIR, name), Buffer.from(await file.arrayBuffer()));
  return `/api/uploads/${name}`;
}
