import nodemailer from 'nodemailer';
import { config } from '@/config/env';
import { prisma } from '@/config/prisma';
import { logger } from '@/utils/logger';

export interface MailPayload {
  to: string | string[];
  subject: string;
  text: string;
  html?: string;
}

/**
 * Transactional mail. Delivery is a no-op unless MAIL_DISABLE_DELIVERY=false
 * and SMTP is configured — matching local/dev where we never want to
 * accidentally hit the production inbox. Failures are logged, never thrown,
 * so a down mail server cannot block order/support flows.
 */
export async function sendMail(payload: MailPayload): Promise<void> {
  const recipients = (Array.isArray(payload.to) ? payload.to : [payload.to])
    .map((address) => address.trim())
    .filter(Boolean);
  if (recipients.length === 0) return;

  if (config.mail.disableDelivery || !config.mail.smtpHost) {
    logger.info({ to: recipients, subject: payload.subject }, 'Mail delivery disabled — skipping send');
    return;
  }

  try {
    const transporter = nodemailer.createTransport({
      host: config.mail.smtpHost,
      port: config.mail.smtpPort,
      secure: config.mail.smtpEncryption === 'ssl' || config.mail.smtpPort === 465,
      auth:
        config.mail.smtpUsername && config.mail.smtpPassword
          ? { user: config.mail.smtpUsername, pass: config.mail.smtpPassword }
          : undefined,
    });

    await transporter.sendMail({
      from: config.mail.fromAddress
        ? `"${config.mail.fromName}" <${config.mail.fromAddress}>`
        : config.mail.fromName,
      to: recipients.join(', '),
      subject: payload.subject,
      text: payload.text,
      html: payload.html,
    });
  } catch (err) {
    logger.error({ err, to: recipients, subject: payload.subject }, 'Failed to send mail');
  }
}

export async function platformNotifyAddresses(): Promise<string[]> {
  const superadmins = await prisma.staffUser.findMany({
    where: { role: 'SUPER_ADMIN', isActive: true, NOT: { email: null } },
    select: { email: true },
  });
  const addresses = superadmins.map((row) => row.email).filter((email): email is string => Boolean(email));
  if (config.mail.notifyAddress) addresses.push(config.mail.notifyAddress);
  return [...new Set(addresses.map((address) => address.trim()).filter(Boolean))];
}
