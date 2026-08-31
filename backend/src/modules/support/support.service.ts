import { SupportMessageStatus, SupportTicketPriority, SupportTicketStatus } from '@prisma/client';
import { prisma } from '@/config/prisma';
import { NotFound } from '@/utils/httpError';
import { notifySuperAdmins, notifyUser } from '@/modules/notifications/notification.service';
import { platformNotifyAddresses, sendMail } from '@/services/mail.service';

const REPLY_INCLUDE = { replies: { orderBy: { createdAt: 'asc' as const }, include: { staff: { select: { fullName: true } } } } };

async function emailStaff(staffId: string | null | undefined, subject: string, text: string) {
  if (!staffId) return;
  const staff = await prisma.staffUser.findUnique({ where: { id: staffId }, select: { email: true } });
  if (staff?.email) await sendMail({ to: staff.email, subject, text });
}

export async function createTicket(restaurantId: string, staffId: string, subject: string, description: string | undefined, priority: SupportTicketPriority) {
  const ticket = await prisma.supportTicket.create({
    data: { restaurantId, staffId, subject, description, priority, status: 'OPEN', channel: 'IN_APP' },
  });
  await notifySuperAdmins(restaurantId, 'support_ticket_created', 'New support ticket', subject, { subject });
  await sendMail({
    to: await platformNotifyAddresses(),
    subject: `New support ticket: ${subject}`,
    text: description ? `${subject}\n\n${description}` : subject,
  });
  return ticket;
}

export async function listMyTickets(staffId: string) {
  return prisma.supportTicket.findMany({
    where: { staffId },
    orderBy: { updatedAt: 'desc' },
    include: { _count: { select: { replies: true } } },
  });
}

export async function getMyTicketDetail(staffId: string, ticketId: string) {
  const ticket = await prisma.supportTicket.findFirst({ where: { id: ticketId, staffId }, include: REPLY_INCLUDE });
  if (!ticket) throw NotFound('Ticket not found');
  return ticket;
}

export async function addStaffReply(staffId: string, ticketId: string, message: string) {
  const ticket = await prisma.supportTicket.findFirst({ where: { id: ticketId, staffId } });
  if (!ticket) throw NotFound('Ticket not found');

  const reply = await prisma.supportTicketReply.create({
    data: { ticketId, staffId, senderType: 'RESTAURANT', isSuperadmin: false, message },
  });
  await prisma.supportTicket.update({ where: { id: ticketId }, data: { lastResponseAt: new Date() } });
  if (ticket.restaurantId) {
    await notifySuperAdmins(ticket.restaurantId, 'support_ticket_reply', 'Support ticket reply', `New reply on: ${ticket.subject}`, {
      subject: ticket.subject,
      variant: 'toSuperadmin',
    });
  }
  await sendMail({
    to: await platformNotifyAddresses(),
    subject: `Support ticket reply: ${ticket.subject}`,
    text: message,
  });
  return reply;
}

const STATUS_RANK: Record<SupportTicketStatus, number> = { OPEN: 0, IN_PROGRESS: 1, WAITING_CUSTOMER: 2, RESOLVED: 3, CLOSED: 4 };
const PRIORITY_RANK: Record<SupportTicketPriority, number> = { URGENT: 0, HIGH: 1, MEDIUM: 2, LOW: 3 };

/**
 * Ported from legacy `getSupportTickets` (app/controllers/superadmin.php),
 * including its triage ordering (open work first, most urgent first). MySQL's
 * `FIELD()` has no direct Prisma equivalent, so the ranking is applied in JS
 * — fine at support-ticket volumes, which are inherently small.
 */
export async function listTickets(filters: { status?: SupportTicketStatus; priority?: SupportTicketPriority; search?: string }, page: number, limit: number) {
  const where: Record<string, unknown> = {};
  if (filters.status) where.status = filters.status;
  if (filters.priority) where.priority = filters.priority;
  if (filters.search) {
    where.OR = [
      { subject: { contains: filters.search, mode: 'insensitive' } },
      { description: { contains: filters.search, mode: 'insensitive' } },
      { contactName: { contains: filters.search, mode: 'insensitive' } },
    ];
  }

  const all = await prisma.supportTicket.findMany({
    where,
    include: { restaurant: { select: { name: true } }, staff: { select: { fullName: true } } },
  });
  all.sort((a, b) => STATUS_RANK[a.status] - STATUS_RANK[b.status] || PRIORITY_RANK[a.priority] - PRIORITY_RANK[b.priority] || b.updatedAt.getTime() - a.updatedAt.getTime());

  const total = all.length;
  const tickets = all.slice((page - 1) * limit, (page - 1) * limit + limit);
  return { tickets, total, page, totalPages: Math.max(1, Math.ceil(total / limit)) };
}

export async function getTicketDetail(ticketId: string) {
  const ticket = await prisma.supportTicket.findUnique({
    where: { id: ticketId },
    include: { restaurant: { select: { name: true } }, ...REPLY_INCLUDE },
  });
  if (!ticket) throw NotFound('Ticket not found');
  return ticket;
}

export async function updateTicketStatus(ticketId: string, status: SupportTicketStatus, assignedTo?: string | null) {
  const ticket = await prisma.supportTicket.findUnique({ where: { id: ticketId } });
  if (!ticket) throw NotFound('Ticket not found');

  const updated = await prisma.supportTicket.update({ where: { id: ticketId }, data: { status, assignedTo: assignedTo ?? null } });

  const statusLabel = status.replace('_', ' ').toLowerCase();
  if (ticket.staffId && ticket.restaurantId) {
    await notifyUser(ticket.restaurantId, ticket.staffId, 'support_ticket_status', 'Support ticket updated', `Your ticket "${ticket.subject}" is now ${statusLabel}`, {
      subject: ticket.subject,
      status,
    });
  }
  await emailStaff(ticket.staffId, `Ticket "${ticket.subject}" is now ${statusLabel}`, `Your support ticket "${ticket.subject}" was updated to ${statusLabel}.`);
  return updated;
}

export async function addAdminReply(superAdminId: string, ticketId: string, message: string) {
  const ticket = await prisma.supportTicket.findUnique({ where: { id: ticketId } });
  if (!ticket) throw NotFound('Ticket not found');

  const reply = await prisma.supportTicketReply.create({
    data: { ticketId, staffId: superAdminId, senderType: 'SUPPORT', isSuperadmin: true, message },
  });
  await prisma.supportTicket.update({ where: { id: ticketId }, data: { lastResponseAt: new Date() } });

  if (ticket.staffId && ticket.restaurantId) {
    await notifyUser(ticket.restaurantId, ticket.staffId, 'support_ticket_reply', 'Support reply received', `Support replied to "${ticket.subject}"`, {
      subject: ticket.subject,
      variant: 'toStaff',
    });
  }
  await emailStaff(ticket.staffId, `Support replied: ${ticket.subject}`, message);
  return reply;
}

export async function createPublicMessage(data: {
  contactName: string;
  contactEmail: string;
  subject: string;
  message: string;
  restaurantName?: string;
}) {
  let restaurantId: string | null = null;
  if (data.restaurantName?.trim()) {
    const restaurant = await prisma.restaurant.findFirst({
      where: {
        OR: [
          { name: { contains: data.restaurantName.trim(), mode: 'insensitive' } },
          { slug: { contains: data.restaurantName.trim(), mode: 'insensitive' } },
        ],
      },
      select: { id: true },
    });
    restaurantId = restaurant?.id ?? null;
  }

  const row = await prisma.supportMessage.create({
    data: {
      restaurantId,
      subject: data.subject,
      message: data.message,
      channel: 'WEB',
      status: 'NEW',
      contactName: data.contactName,
      contactEmail: data.contactEmail,
    },
  });

  await sendMail({
    to: await platformNotifyAddresses(),
    subject: `New inquiry: ${data.subject}`,
    text: [`From: ${data.contactName} <${data.contactEmail}>`, data.restaurantName ? `Restaurant: ${data.restaurantName}` : null, '', data.message]
      .filter(Boolean)
      .join('\n'),
  });

  return { id: row.id };
}

export async function listPublicMessages() {
  return prisma.supportMessage.findMany({
    include: { restaurant: { select: { name: true } } },
    orderBy: { createdAt: 'desc' },
    take: 50,
  });
}

export async function markPublicMessageRead(messageId: string) {
  const message = await prisma.supportMessage.findUnique({ where: { id: messageId } });
  if (!message) throw NotFound('Message not found');
  return prisma.supportMessage.update({ where: { id: messageId }, data: { status: SupportMessageStatus.READ } });
}

export async function archivePublicMessage(messageId: string) {
  const message = await prisma.supportMessage.findUnique({ where: { id: messageId } });
  if (!message) throw NotFound('Message not found');
  return prisma.supportMessage.update({ where: { id: messageId }, data: { status: SupportMessageStatus.ARCHIVED } });
}
