import { reviewOperatorAction } from '@/app/actions/admin';

/** Approve / reject buttons for a tour operator (used on the admin overview and operators pages). */
export default function OperatorReview({ id, status }: { id: number; status: string }) {
  return (
    <form action={reviewOperatorAction} className="btn-row" style={{ alignItems: 'center' }}>
      <input type="hidden" name="id" value={id} />
      {status !== 'approved' && <button className="btn btn-primary btn-sm" name="decision" value="approve">Approve</button>}
      {status !== 'rejected' && <>
        <input name="note" placeholder="Reason (if rejecting)" style={{ width: 170, padding: '6px 8px', fontSize: '.84rem' }} />
        <button className="btn btn-danger btn-sm" name="decision" value="reject">Reject</button></>}
    </form>
  );
}
