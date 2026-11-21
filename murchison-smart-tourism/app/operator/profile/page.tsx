import type { Metadata } from 'next';
import DashShell from '@/components/DashShell';
import OperatorGate from '@/components/OperatorGate';
import { OperatorProfileForm } from '@/components/forms/BookingForms';
import { operatorFor, requireUser } from '@/lib/auth';

export const metadata: Metadata = { title: 'Company profile' };

export default async function OperatorProfile() {
  const u = await requireUser(['operator']);
  const op = await operatorFor(u.id);
  return (
    <DashShell user={u} active="/operator/profile" title="Company profile">
      <OperatorGate op={op} />
      {op && <OperatorProfileForm op={op} />}
    </DashShell>
  );
}
