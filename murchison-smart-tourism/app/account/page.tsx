import type { Metadata } from 'next';
import DashShell from '@/components/DashShell';
import { ProfileForms } from '@/components/forms/AuthForms';
import { requireUser } from '@/lib/auth';

export const metadata: Metadata = { title: 'My account' };

export default async function AccountPage() {
  const u = await requireUser();
  return (
    <DashShell user={u} active="/account" title="My account">
      <ProfileForms name={u.name} phone={u.phone} />
    </DashShell>
  );
}
