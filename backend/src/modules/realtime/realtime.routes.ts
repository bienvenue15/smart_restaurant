import { Request, Response } from 'express';
import { eventVisibleToCustomer, eventVisibleToStaff, subscribeRealtime } from '@/services/realtime.service';

function writeSseHeaders(res: Response): void {
  res.setHeader('Content-Type', 'text/event-stream');
  res.setHeader('Cache-Control', 'no-cache, no-transform');
  res.setHeader('Connection', 'keep-alive');
  res.setHeader('X-Accel-Buffering', 'no');
  res.flushHeaders?.();
}

export function streamStaffEvents(req: Request, res: Response): void {
  writeSseHeaders(res);
  res.write(`event: ready\ndata: ${JSON.stringify({ ok: true })}\n\n`);

  const staff = req.staff!;
  const unsubscribe = subscribeRealtime((event) => {
    if (!eventVisibleToStaff(event, staff)) return;
    res.write(`event: ${event.type}\ndata: ${JSON.stringify(event)}\n\n`);
  });

  const heartbeat = setInterval(() => {
    res.write(': ping\n\n');
  }, 25000);

  req.on('close', () => {
    clearInterval(heartbeat);
    unsubscribe();
    res.end();
  });
}

export function streamCustomerEvents(req: Request, res: Response): void {
  writeSseHeaders(res);
  res.write(`event: ready\ndata: ${JSON.stringify({ ok: true })}\n\n`);

  const session = req.customerSession!;
  const unsubscribe = subscribeRealtime((event) => {
    if (!eventVisibleToCustomer(event, session)) return;
    res.write(`event: ${event.type}\ndata: ${JSON.stringify(event)}\n\n`);
  });

  const heartbeat = setInterval(() => {
    res.write(': ping\n\n');
  }, 25000);

  req.on('close', () => {
    clearInterval(heartbeat);
    unsubscribe();
    res.end();
  });
}
