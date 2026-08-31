import { config } from '@/config/env';
import { logger } from '@/utils/logger';

export interface OutboundMessage {
  to: string;
  text: string;
}

function normalizePhone(raw: string): string | null {
  const digits = raw.replace(/[^\d+]/g, '');
  if (!digits) return null;
  if (digits.startsWith('+')) return digits;
  if (digits.startsWith('0') && digits.length === 10) return `+250${digits.slice(1)}`;
  if (digits.startsWith('250') && digits.length === 12) return `+${digits}`;
  if (digits.length >= 9) return `+${digits}`;
  return null;
}

/**
 * SMS via Africa's Talking + WhatsApp via Meta Cloud API.
 * Both no-op unless explicitly enabled, matching mail.service.ts so local
 * development never accidentally bills a live gateway.
 */
export async function sendSms(payload: OutboundMessage): Promise<void> {
  const to = normalizePhone(payload.to);
  if (!to) return;
  if (config.sms.disableDelivery || !config.sms.apiKey || !config.sms.username) {
    logger.info({ to }, 'SMS delivery disabled — skipping send');
    return;
  }

  try {
    const body = new URLSearchParams({
      username: config.sms.username,
      to,
      message: payload.text,
    });
    if (config.sms.senderId) body.set('from', config.sms.senderId);

    const response = await fetch(config.sms.apiUrl, {
      method: 'POST',
      headers: {
        apiKey: config.sms.apiKey,
        Accept: 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body,
    });
    if (!response.ok) {
      logger.error({ to, status: response.status, body: await response.text() }, 'SMS gateway rejected the message');
    }
  } catch (err) {
    logger.error({ err, to }, 'Failed to send SMS');
  }
}

export async function sendWhatsApp(payload: OutboundMessage): Promise<void> {
  const to = normalizePhone(payload.to)?.replace(/^\+/, '');
  if (!to) return;
  if (config.whatsapp.disableDelivery || !config.whatsapp.token || !config.whatsapp.phoneNumberId) {
    logger.info({ to }, 'WhatsApp delivery disabled — skipping send');
    return;
  }

  try {
    const response = await fetch(`https://graph.facebook.com/v21.0/${config.whatsapp.phoneNumberId}/messages`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${config.whatsapp.token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        messaging_product: 'whatsapp',
        to,
        type: 'text',
        text: { body: payload.text },
      }),
    });
    if (!response.ok) {
      logger.error({ to, status: response.status, body: await response.text() }, 'WhatsApp API rejected the message');
    }
  } catch (err) {
    logger.error({ err, to }, 'Failed to send WhatsApp message');
  }
}

export async function notifyPhone(phone: string | null | undefined, text: string): Promise<void> {
  if (!phone?.trim()) return;
  await Promise.all([sendSms({ to: phone, text }), sendWhatsApp({ to: phone, text })]);
}
