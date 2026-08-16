import multer from 'multer';

// Buffered in memory (images are capped small, 2MB) so the upload service
// can sniff real content before anything touches disk — see
// utils/imageSignature.ts and modules/uploads/upload.service.ts.
export const uploadImage = multer({
  storage: multer.memoryStorage(),
  limits: { fileSize: 2 * 1024 * 1024 },
}).single('image');
