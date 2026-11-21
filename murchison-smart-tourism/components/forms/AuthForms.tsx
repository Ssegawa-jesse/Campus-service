'use client';
import Link from 'next/link';
import { useActionState, useState } from 'react';
import { loginAction, registerAction, changePasswordAction, updateProfileAction, type FormState } from '@/app/actions/auth';

export function Notice({ state }: { state: FormState }) {
  if (state.error) return <div className="notice warn" role="alert">{state.error}</div>;
  if (state.ok) return <div className="notice info" role="status">{state.ok}</div>;
  return null;
}

export function LoginForm({ next }: { next?: string }) {
  const [state, action, pending] = useActionState(loginAction, {});
  return (
    <form action={action} className="card">
      <h2 style={{ fontSize: '1.3rem' }}>Log in</h2>
      <Notice state={state} />
      <input type="hidden" name="next" value={next ?? ''} />
      <div className="field"><label htmlFor="email">Email</label><input id="email" name="email" type="text" inputMode="email" autoComplete="email" required /></div>
      <div className="field"><label htmlFor="password">Password</label><input id="password" name="password" type="password" autoComplete="current-password" required /></div>
      <button className="btn btn-primary" style={{ width: '100%' }} disabled={pending}>{pending ? 'Logging in…' : 'Log in'}</button>
      <p className="small muted" style={{ marginTop: 12 }}>No account yet? <Link href={`/register${next ? `?next=${encodeURIComponent(next)}` : ''}`}>Register as a tourist or tour operator</Link>.</p>
    </form>
  );
}

export function RegisterForm({ next, initialRole }: { next?: string; initialRole?: string }) {
  const [state, action, pending] = useActionState(registerAction, {});
  const [role, setRole] = useState(initialRole === 'operator' ? 'operator' : 'tourist');
  return (
    <form action={action} className="card">
      <h2 style={{ fontSize: '1.3rem' }}>Create an account</h2>
      <Notice state={state} />
      <input type="hidden" name="next" value={next ?? ''} />
      <input type="hidden" name="role" value={role} />
      <div className="field"><label>I am a</label>
        <div className="segmented">
          <button type="button" className={role === 'tourist' ? 'on' : ''} onClick={() => setRole('tourist')}>Tourist / visitor</button>
          <button type="button" className={role === 'operator' ? 'on' : ''} onClick={() => setRole('operator')}>Tour operator</button>
        </div></div>
      <div className="field"><label htmlFor="name">Full name</label><input id="name" name="name" type="text" autoComplete="name" required /></div>
      <div className="row">
        <div className="field"><label htmlFor="email">Email</label><input id="email" name="email" type="text" inputMode="email" autoComplete="email" required /></div>
        <div className="field"><label htmlFor="phone">Phone (for SMS)</label><input id="phone" name="phone" type="text" inputMode="tel" placeholder="0772 123 456" required /></div>
      </div>
      {role === 'operator' && (<>
        <div className="row">
          <div className="field"><label htmlFor="company">Company name</label><input id="company" name="company" type="text" required /></div>
          <div className="field"><label htmlFor="licence">UTB licence number (optional)</label><input id="licence" name="licence" type="text" /></div>
        </div>
        <div className="field"><label htmlFor="description">About your company</label><textarea id="description" name="description" rows={3} /></div>
        <div className="notice info">Operator accounts are checked and approved by an administrator before tourists can book you.</div>
      </>)}
      <div className="row">
        <div className="field"><label htmlFor="password">Password (8+ characters)</label><input id="password" name="password" type="password" autoComplete="new-password" required minLength={8} /></div>
        <div className="field"><label htmlFor="confirm">Confirm password</label><input id="confirm" name="confirm" type="password" autoComplete="new-password" required /></div>
      </div>
      <button className="btn btn-primary" style={{ width: '100%' }} disabled={pending}>{pending ? 'Creating account…' : 'Create account'}</button>
      <p className="small muted" style={{ marginTop: 12 }}>Already registered? <Link href="/login">Log in</Link>.</p>
    </form>
  );
}

export function ProfileForms({ name, phone }: { name: string; phone: string | null }) {
  const [p, profileAction, p1] = useActionState(updateProfileAction, {});
  const [c, passwordAction, p2] = useActionState(changePasswordAction, {});
  return (
    <div className="grid grid-2">
      <form action={profileAction} className="card">
        <h3>My details</h3>
        <Notice state={p} />
        <div className="field"><label htmlFor="pname">Full name</label><input id="pname" name="name" defaultValue={name} required /></div>
        <div className="field"><label htmlFor="pphone">Phone (for SMS)</label><input id="pphone" name="phone" defaultValue={phone ?? ''} required /></div>
        <button className="btn btn-dark" disabled={p1}>Save details</button>
      </form>
      <form action={passwordAction} className="card">
        <h3>Change password</h3>
        <Notice state={c} />
        <div className="field"><label htmlFor="cur">Current password</label><input id="cur" name="current" type="password" autoComplete="current-password" required /></div>
        <div className="row">
          <div className="field"><label htmlFor="np">New password</label><input id="np" name="password" type="password" autoComplete="new-password" minLength={8} required /></div>
          <div className="field"><label htmlFor="nc">Confirm</label><input id="nc" name="confirm" type="password" autoComplete="new-password" required /></div>
        </div>
        <button className="btn btn-dark" disabled={p2}>Change password</button>
      </form>
    </div>
  );
}
