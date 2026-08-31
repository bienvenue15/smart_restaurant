import { Router } from 'express';
import { requireStaffAuth } from '@/middleware/auth';
import { requireSuperAdmin } from '@/middleware/superAdmin';
import { validate } from '@/middleware/validate';
import { createPublicMessageSchema, createTicketSchema, replyTicketSchema, updateTicketStatusSchema } from '@/validators/support.validators';
import * as supportService from './support.service';

// Restaurant-staff-facing: submit a ticket, view/reply to your own — no
// special permission beyond being logged in, matching legacy (any staff
// with a session could create/view their own tickets).
export const staffSupportRouter = Router();
staffSupportRouter.use(requireStaffAuth);

staffSupportRouter.post('/', validate(createTicketSchema), async (req, res, next) => {
  try {
    const ticket = await supportService.createTicket(req.staff!.restaurantId!, req.staff!.id, req.body.subject, req.body.description, req.body.priority);
    res.status(201).json({ status: 'OK', data: ticket });
  } catch (err) {
    next(err);
  }
});

staffSupportRouter.get('/', async (req, res, next) => {
  try {
    const tickets = await supportService.listMyTickets(req.staff!.id);
    res.json({ status: 'OK', data: tickets });
  } catch (err) {
    next(err);
  }
});

staffSupportRouter.get('/:id', async (req, res, next) => {
  try {
    const ticket = await supportService.getMyTicketDetail(req.staff!.id, req.params.id!);
    res.json({ status: 'OK', data: ticket });
  } catch (err) {
    next(err);
  }
});

staffSupportRouter.post('/:id/replies', validate(replyTicketSchema), async (req, res, next) => {
  try {
    const reply = await supportService.addStaffReply(req.staff!.id, req.params.id!, req.body.message);
    res.status(201).json({ status: 'OK', data: reply });
  } catch (err) {
    next(err);
  }
});

// Superadmin-facing helpdesk.
export const adminSupportRouter = Router();
adminSupportRouter.use(requireStaffAuth, requireSuperAdmin);

adminSupportRouter.get('/', async (req, res, next) => {
  try {
    const page = Math.max(1, parseInt(String(req.query.page ?? '1'), 10) || 1);
    const limit = Math.min(100, Math.max(10, parseInt(String(req.query.limit ?? '20'), 10) || 20));
    const result = await supportService.listTickets(
      {
        status: req.query.status as never,
        priority: req.query.priority as never,
        search: req.query.search as string | undefined,
      },
      page,
      limit,
    );
    res.json({ status: 'OK', data: result });
  } catch (err) {
    next(err);
  }
});

adminSupportRouter.get('/:id', async (req, res, next) => {
  try {
    const ticket = await supportService.getTicketDetail(req.params.id!);
    res.json({ status: 'OK', data: ticket });
  } catch (err) {
    next(err);
  }
});

adminSupportRouter.patch('/:id/status', validate(updateTicketStatusSchema), async (req, res, next) => {
  try {
    const ticket = await supportService.updateTicketStatus(req.params.id!, req.body.status, req.body.assignedTo);
    res.json({ status: 'OK', data: ticket });
  } catch (err) {
    next(err);
  }
});

adminSupportRouter.post('/:id/replies', validate(replyTicketSchema), async (req, res, next) => {
  try {
    const reply = await supportService.addAdminReply(req.staff!.id, req.params.id!, req.body.message);
    res.status(201).json({ status: 'OK', data: reply });
  } catch (err) {
    next(err);
  }
});

export const publicSupportRouter = Router();

publicSupportRouter.post('/messages', validate(createPublicMessageSchema), async (req, res, next) => {
  try {
    const message = await supportService.createPublicMessage(req.body);
    res.status(201).json({ status: 'OK', data: message });
  } catch (err) {
    next(err);
  }
});

export const adminSupportMessageRouter = Router();
adminSupportMessageRouter.use(requireStaffAuth, requireSuperAdmin);

adminSupportMessageRouter.get('/', async (_req, res, next) => {
  try {
    res.json({ status: 'OK', data: await supportService.listPublicMessages() });
  } catch (err) {
    next(err);
  }
});

adminSupportMessageRouter.patch('/:id/read', async (req, res, next) => {
  try {
    res.json({ status: 'OK', data: await supportService.markPublicMessageRead(req.params.id!) });
  } catch (err) {
    next(err);
  }
});

adminSupportMessageRouter.patch('/:id/archive', async (req, res, next) => {
  try {
    res.json({ status: 'OK', data: await supportService.archivePublicMessage(req.params.id!) });
  } catch (err) {
    next(err);
  }
});
