/**
 * Real content sniffing via magic bytes, not the client-reported
 * Content-Type header — preserves the one thing the legacy upload handler
 * got right (docs/SECURITY_AUDIT.md's "What the legacy app got right":
 * real MIME-sniffing via PHP's finfo, not trusting client-supplied
 * Content-Type/extension).
 */
const SIGNATURES: { mime: string; check: (buf: Buffer) => boolean }[] = [
  { mime: 'image/jpeg', check: (b) => b.length > 3 && b[0] === 0xff && b[1] === 0xd8 && b[2] === 0xff },
  { mime: 'image/png', check: (b) => b.length > 4 && b[0] === 0x89 && b[1] === 0x50 && b[2] === 0x4e && b[3] === 0x47 },
  { mime: 'image/gif', check: (b) => b.length > 4 && b[0] === 0x47 && b[1] === 0x49 && b[2] === 0x46 && b[3] === 0x38 },
  {
    mime: 'image/webp',
    check: (b) => b.length > 12 && b.subarray(0, 4).toString('ascii') === 'RIFF' && b.subarray(8, 12).toString('ascii') === 'WEBP',
  },
];

export function sniffImageMime(buffer: Buffer): string | null {
  return SIGNATURES.find((s) => s.check(buffer))?.mime ?? null;
}
