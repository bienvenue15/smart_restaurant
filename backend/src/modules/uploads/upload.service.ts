import crypto from 'node:crypto';
import fs from 'node:fs/promises';
import path from 'node:path';
import { BadRequest } from '@/utils/httpError';
import { sniffImageMime } from '@/utils/imageSignature';

const UPLOAD_ROOT = path.resolve(__dirname, '../../../uploads');
const MAX_IMAGE_BYTES = 2 * 1024 * 1024; // 2MB — legacy caps were 500KB-1MB; a bit more headroom since we don't recompress server-side yet

const MIME_TO_EXT: Record<string, string> = {
  'image/jpeg': 'jpg',
  'image/png': 'png',
  'image/gif': 'gif',
  'image/webp': 'webp',
};

/**
 * Saves a menu item image after verifying its actual content (magic
 * bytes), not the client-supplied Content-Type or filename extension —
 * see utils/imageSignature.ts. Filename is a fresh random token, never
 * derived from client input, so there is no path-traversal surface.
 */
export async function saveMenuItemImage(restaurantId: string, buffer: Buffer): Promise<string> {
  if (buffer.length === 0) throw BadRequest('Empty file');
  if (buffer.length > MAX_IMAGE_BYTES) throw BadRequest(`Image too large (max ${MAX_IMAGE_BYTES / 1024 / 1024}MB)`);

  const mime = sniffImageMime(buffer);
  if (!mime) throw BadRequest('File is not a recognized image (jpeg/png/gif/webp)');

  const dir = path.join(UPLOAD_ROOT, 'menu', restaurantId);
  await fs.mkdir(dir, { recursive: true });

  const filename = `${crypto.randomUUID()}.${MIME_TO_EXT[mime]}`;
  await fs.writeFile(path.join(dir, filename), buffer);

  return `/uploads/menu/${restaurantId}/${filename}`;
}
