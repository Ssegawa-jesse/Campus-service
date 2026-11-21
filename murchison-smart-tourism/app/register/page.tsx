import type { Metadata } from 'next';
import { redirect } from 'next/navigation';
import { RegisterForm } from '@/components/forms/AuthForms';
import { currentUser, dashboardFor } from '@/lib/auth';

export const metadata: Metadata = { title: 'Register' };

export default async function RegisterPage({ searchParams }: { searchParams: Promise<{ next?: string; role?: string }> }) {
  const { next, role } = await searchParams;
  const u = await currentUser();
  if (u) redirect(dashboardFor(u.role));
  return (
    <section className="section"><div className="container auth-wrap">
      <RegisterForm next={next} initialRole={role} />
    </div></section>
  );
}
