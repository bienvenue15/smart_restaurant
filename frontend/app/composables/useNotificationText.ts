import type { StaffNotification } from '~/types/notification';

function formatNumber(v: unknown): string {
  const n = Number(v);
  return Number.isFinite(n) ? n.toLocaleString() : String(v ?? '');
}

function capitalize(s: string): string {
  return s.length > 0 ? s.charAt(0).toUpperCase() + s.slice(1) : s;
}

/**
 * Notification `title`/`message` are written server-side in English (see
 * the notifyRoles/notifyUser call sites in the backend) purely as a DB
 * record and SMS/email fallback. The UI instead renders from `type` +
 * `data` through i18n, so notifications actually translate. Falls back to
 * the raw API title/message for any type this doesn't recognize, or for
 * notifications created before `data` payloads existed.
 */
export function useNotificationText() {
  const { t } = useI18n();

  function render(n: StaffNotification): { title: string; message: string } {
    const data = (n.data ?? {}) as Record<string, unknown>;
    const fallback = { title: n.title, message: n.message };
    const has = (...keys: string[]) => keys.every((k) => data[k] !== undefined && data[k] !== null);

    switch (n.type) {
      case 'waiter_call': {
        if (!has('tableNumber', 'requestType')) return fallback;
        const requestType = String(data.requestType).toLowerCase();
        return {
          title: t('notifications.waiterCall.title'),
          message: t('notifications.waiterCall.message', {
            table: data.tableNumber,
            type: t(`staff.waiterCalls.requestType.${requestType}`),
          }),
        };
      }
      case 'approval_needed': {
        if (data.kind === 'cash_close') {
          if (!has('counted', 'expected')) return fallback;
          return {
            title: t('notifications.approvalNeeded.cashCloseTitle'),
            message: t('notifications.approvalNeeded.cashCloseMessage', {
              name: data.staffName ?? '',
              counted: formatNumber(data.counted),
              expected: formatNumber(data.expected),
            }),
          };
        }
        if (!has('kind', 'orderNumber', 'amount')) return fallback;
        const kind = data.kind === 'refund' ? t('staff.orders.refund') : t('staff.orders.discount');
        const message =
          data.kind === 'refund'
            ? t('notifications.approvalNeeded.refundMessage', { amount: formatNumber(data.amount), order: data.orderNumber })
            : t('notifications.approvalNeeded.discountMessage', { percent: data.percent, amount: formatNumber(data.amount), order: data.orderNumber });
        return { title: t('notifications.approvalNeeded.title', { kind }), message };
      }
      case 'approval_resolved': {
        if (data.kind === 'cash_close') {
          const approved = data.decision === 'approved';
          return {
            title: approved
              ? t('notifications.approvalResolved.cashCloseTitleApproved')
              : t('notifications.approvalResolved.cashCloseTitleRejected'),
            message: approved
              ? t('notifications.approvalResolved.cashCloseMessageApproved', { counted: formatNumber(data.counted) })
              : t('notifications.approvalResolved.cashCloseMessageRejected'),
          };
        }
        if (!has('kind', 'decision', 'orderNumber', 'amount')) return fallback;
        const kindLower = (data.kind === 'refund' ? t('staff.orders.refund') : t('staff.orders.discount')).toLowerCase();
        const approved = data.decision === 'approved';
        const params = { kind: kindLower, amount: formatNumber(data.amount), order: data.orderNumber };
        return {
          title: approved
            ? t('notifications.approvalResolved.titleApproved', { kind: capitalize(kindLower) })
            : t('notifications.approvalResolved.titleRejected', { kind: capitalize(kindLower) }),
          message: approved
            ? t('notifications.approvalResolved.messageApproved', params)
            : data.audience === 'cashier'
              ? t('notifications.approvalResolved.messageRejectedCashier', params)
              : t('notifications.approvalResolved.messageRejected', params),
        };
      }
      case 'menu_availability': {
        if (!has('itemName', 'isAvailable')) return fallback;
        const status = data.isAvailable ? t('notifications.menuAvailability.available') : t('notifications.menuAvailability.unavailable');
        return {
          title: t('notifications.menuAvailability.title'),
          message: t('notifications.menuAvailability.message', { item: data.itemName, status }),
        };
      }
      case 'order_items_added': {
        if (!has('orderNumber')) return fallback;
        return {
          title: t('notifications.orderItemsAdded.title'),
          message: data.needsReconfirm
            ? t('notifications.orderItemsAdded.reconfirm', { order: data.orderNumber })
            : t('notifications.orderItemsAdded.added', { order: data.orderNumber }),
        };
      }
      case 'order_received': {
        if (!has('orderNumber', 'tableNumber')) return fallback;
        return {
          title: t('notifications.orderReceived.title'),
          message: t('notifications.orderReceived.message', { order: data.orderNumber, table: data.tableNumber }),
        };
      }
      case 'new_order': {
        if (!has('orderNumber')) return fallback;
        return { title: t('notifications.newOrder.title'), message: t('notifications.newOrder.message', { order: data.orderNumber }) };
      }
      case 'order_assignment': {
        if (!has('tableNumber', 'orderNumber')) return fallback;
        return {
          title: t('notifications.orderAssignment.title'),
          message: t('notifications.orderAssignment.message', { table: data.tableNumber, order: data.orderNumber }),
        };
      }
      case 'order_delay_reminder': {
        if (!has('orderNumber', 'tableNumber', 'delayMinutes')) return fallback;
        return {
          title: t('notifications.orderDelayReminder.title'),
          message: t('notifications.orderDelayReminder.message', { order: data.orderNumber, table: data.tableNumber, minutes: data.delayMinutes }),
        };
      }
      case 'order_delay_escalation': {
        if (!has('orderNumber', 'tableNumber', 'delayMinutes')) return fallback;
        const params = { order: data.orderNumber, table: data.tableNumber, minutes: data.delayMinutes };
        return {
          title: t('notifications.orderDelayEscalation.title'),
          message:
            data.variant === 'waiter'
              ? t('notifications.orderDelayEscalation.waiterMessage', params)
              : t('notifications.orderDelayEscalation.managerMessage', params),
        };
      }
      case 'support_ticket_created': {
        if (!has('subject')) return fallback;
        return { title: t('notifications.supportTicketCreated.title'), message: String(data.subject) };
      }
      case 'support_ticket_reply': {
        if (!has('subject')) return fallback;
        const toStaff = data.variant === 'toStaff';
        return {
          title: toStaff ? t('notifications.supportTicketReply.titleToStaff') : t('notifications.supportTicketReply.titleToSuperadmin'),
          message: toStaff
            ? t('notifications.supportTicketReply.messageToStaff', { subject: data.subject })
            : t('notifications.supportTicketReply.messageToSuperadmin', { subject: data.subject }),
        };
      }
      case 'support_ticket_status': {
        if (!has('subject', 'status')) return fallback;
        const statusKey = String(data.status).toLowerCase();
        return {
          title: t('notifications.supportTicketStatus.title'),
          message: t('notifications.supportTicketStatus.message', { subject: data.subject, status: t(`staff.support.status.${statusKey}`) }),
        };
      }
      default:
        return fallback;
    }
  }

  return { render };
}
