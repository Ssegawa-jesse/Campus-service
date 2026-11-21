import type { Operator } from '@/lib/db';

/** Message shown to operators whose account is not approved yet. */
export default function OperatorGate({ op }: { op: Operator | null }) {
  if (!op) return <div className="notice warn">Your operator profile is missing. Contact the administrator.</div>;
  if (op.status === 'pending') return <div className="notice warn">⏳ Your account is waiting for approval by an administrator. You can prepare your packages now; tourists will see you once you are approved.</div>;
  if (op.status === 'rejected') return <div className="notice warn">Your account was not approved.{op.review_note ? ` Reason: ${op.review_note}` : ''} Contact the administrator for help.</div>;
  return null;
}
