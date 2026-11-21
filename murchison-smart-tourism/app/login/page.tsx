import type { Metadata } from 'next';
import { redirect } from 'next/navigation';
import { LoginForm } from '@/components/forms/AuthForms';
import { currentUser, dashboardFor } from '@/lib/auth';

export const metadata: Metadata = { title: 'Log in' };

export default async function LoginPage({ searchParams }: { searchParams: Promise<{ next?: string }> }) {
  const { next } = await searchParams;
  const u = await currentUser();
  if (u) redirect(dashboardFor(u.role));
  return (
    <section className="section"><div className="container auth-wrap">
      <LoginForm next={next} />
    </div></section>
  );
}
