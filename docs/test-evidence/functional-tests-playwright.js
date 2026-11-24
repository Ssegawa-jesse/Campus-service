const { chromium } = require('playwright');
const fs = require('fs');
const base = 'http://127.0.0.1:8123', out = __dirname, results = [];
(async () => {
  const b = await chromium.launch(); const ctx = await b.newContext({ viewport: { width: 1366, height: 860 } }); const p = await ctx.newPage();
  const tc = async (id, title, steps, expected, fn) => {
    let actual = '', status = 'Fail';
    try { const r = await fn(); actual = r[1]; status = r[0] ? 'Pass' : 'Fail'; } catch (e) { actual = 'Error: ' + e.message.split('\n')[0]; }
    await p.screenshot({ path: `${out}/${id}.png` }).catch(() => {});
    results.push({ id, title, steps, expected, actual, status }); console.log(id, status, actual);
  };
  const go = async u => { await p.goto(base + u, { waitUntil: 'networkidle' }).catch(() => {}); };
  const login = async (email) => { await p.context().clearCookies(); await go('/login'); await p.fill('input[name=email]', email); await p.fill('input[name=password]', 'Pearl360@2026'); await Promise.all([p.waitForNavigation().catch(()=>{}), p.click('button:has-text("Log in")')]); };

  await tc('TC01', 'Home page loads with six modules', 'Open the home page', 'Page shows the six module cards and live travel updates', async () => {
    await go('/'); const n = await p.locator('.module-card').count(); const u = await p.locator('text=Live travel updates').count();
    return [n === 6 && u > 0, `${n} module cards shown; travel updates panel ${u ? 'shown' : 'missing'}`]; });
  await tc('TC02', 'Generate a 3-day Smart Itinerary', 'Open Smart Itinerary, keep defaults (3 days, 2 adults, Foreign Non-Resident), click Generate', 'A timed plan for Day 1–3 with a total cost is shown', async () => {
    await go('/itinerary'); await Promise.all([p.waitForNavigation(), p.click('button:has-text("Generate my itinerary")')]);
    const days = await p.locator('text=/^Day [0-9]$/').count(); const total = (await p.locator('.display-6').first().textContent()).trim();
    return [days === 3 && /UGX/.test(total), `${days} day cards generated; total ${total}`]; });
  await tc('TC03', 'Itinerary includes Paraa ferry for North Bank activities', 'Inspect the generated plan from TC02', 'A “Paraa ferry” item appears before the North Bank game drive', async () => {
    const f = await p.locator('text=Paraa ferry to the North Bank').count(); const g = await p.locator('text=North Bank Game Drive').count();
    return [f > 0 && g > 0, `ferry item ${f ? 'present' : 'absent'}; game drive ${g ? 'present' : 'absent'}`]; });
  await tc('TC04', 'Budget uses UGX tariffs for East African Citizens', 'Open Budget, choose East African Citizen', 'Park entry shown as UGX 25,000 per adult per day and total in UGX', async () => {
    await go('/budget?category=eac&days=2&adults=2&tier=budget&transport=arranged&activities[]=victoria-nile-boat-cruise');
    const t = await p.locator('table').first().innerText(); const ok = /UGX 25,000/.test(t) && /UGX 100,000/.test(t);
    return [ok, ok ? 'Entry line: UGX 25,000 tariff; 2 days × 2 adults = UGX 100,000' : 'Expected values not found']; });
  await tc('TC05', 'Budget uses USD tariffs for Foreign Non-Residents', 'Open Budget, choose Foreign Non-Resident', 'Park entry calculated from US$45 per adult per day', async () => {
    await go('/budget?category=fnr&days=2&adults=2&tier=budget&transport=arranged&activities[]=victoria-nile-boat-cruise');
    const t = await p.locator('table').first().innerText(); return [/US\$45/.test(t), /US\$45/.test(t) ? 'Entry line shows @ US$45 converted to UGX' : 'US$45 not found']; });
  await tc('TC06', 'Route distance and travel time', 'Open Navigation; route Kichumbanyobo Gate → Paraa Jetty; click Distance & travel time', 'Road distance (km) and travel time are displayed', async () => {
    await go('/navigate'); await p.click('#routeBtn'); await p.waitForTimeout(1200); const t = await p.locator('#routeOut').innerText();
    return [/km/.test(t) && /min/.test(t), t.replace(/\s+/g, ' ').trim()]; });
  await tc('TC07', 'AI assistant answers a packing question', 'Open AI Assistant; ask “What should I pack?”', 'A packing list answer is returned', async () => {
    await go('/assistant'); await p.fill('#q', 'What should I pack?'); await p.click('#chatForm button'); await p.waitForTimeout(1500);
    const t = await p.locator('.chat-msg.bot').last().innerText(); return [/yellow fever|repellent/i.test(t), t.slice(0, 110).replace(/\s+/g, ' ') + '…']; });
  await tc('TC08', 'AI assistant recommends itinerary for a budget', 'Ask “Plan 3 days with $1,200 for wildlife”', 'Answer lists Day 1–3 activities and an estimated total', async () => {
    await p.fill('#q', 'Plan 3 days with $1,200 for wildlife'); await p.click('#chatForm button'); await p.waitForTimeout(1800);
    const t = await p.locator('.chat-msg.bot').last().innerText(); return [/Day 3/.test(t) && /Estimated total/.test(t), t.split('\n').slice(0, 2).join(' ').slice(0, 120) + '…']; });
  await tc('TC09', 'Travel updates are displayed', 'Open Updates', 'Active alerts (e.g. Paraa ferry schedule) are listed', async () => {
    await go('/updates'); const n = await p.locator('.card .badge').count(); return [n >= 3, `${n} active alerts shown`]; });
  await tc('TC10', '360° scene loads and adds activity to itinerary', 'Open 360° Explore; click a hotspot with “Add to itinerary” via the API used by the hotspot button', 'Scene list shown; activity added to the itinerary basket', async () => {
    await go('/explore'); await p.waitForTimeout(2500); const scenes = await p.locator('.scene-pick').count();
    const r = await p.evaluate(async () => { const t = document.querySelector('meta[name=csrf-token]').content; const x = await fetch('/itinerary/add/night-game-drive', { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': t } }); return x.json(); });
    return [scenes >= 7 && r.ok, `${scenes} scenes listed; response: ${r.message}`]; });
  await tc('TC11', 'Role protection: tourist cannot open admin', 'Log in as tourist; open /admin', 'Access denied (HTTP 403)', async () => {
    await login('tourist@pearl360.test'); const r = await p.goto(base + '/admin'); return [r.status() === 403, `HTTP ${r.status()} returned`]; });
  await tc('TC12', 'Tourist saves, books and pays for an itinerary (MTN MoMo)', 'As tourist: generate, save, book with an operator, pay with MTN Mobile Money (simulator), approve', 'Booking status becomes Paid', async () => {
    await go('/itinerary'); await Promise.all([p.waitForNavigation(), p.click('button:has-text("Generate my itinerary")')]);
    await Promise.all([p.waitForNavigation(), p.click('button:has-text("Save itinerary")')]);
    await p.fill('input[name=contact_phone]', '0741141433'); await Promise.all([p.waitForNavigation(), p.click('button:has-text("& pay")')]);
    const ref = p.url().split('/bookings/')[1]?.split('/')[0];
    const mtn = p.locator('input[value=mobile_money_mtn]'); if (await mtn.count()) await mtn.check({ force: true });
    const ph = p.locator('input[name=phone]'); if (await ph.count()) await ph.fill('0741141433');
    await Promise.all([p.waitForNavigation(), p.click('button:has-text("Pay UGX")')]);
    const pin = p.locator('input[name=pin]'); if (await pin.count()) await pin.fill('1234');
    await Promise.all([p.waitForNavigation().catch(()=>{}), p.click('button[name=decision][value=approve]')]);
    const body = await p.locator('body').innerText(); const paid = /Paid|payment received|Payment successful/i.test(body);
    return [paid, `Booking ${ref || ''} – ${paid ? 'status Paid shown' : 'paid status not shown'}`]; });
  await tc('TC13', 'SMS and email confirmations logged', 'Log in as admin; open SMS & email logs', 'Payment confirmation SMS to +256741141433 and email to 2400725284@mubs.ac.ug are listed', async () => {
    await login('2400725284@mubs.ac.ug'); await go('/admin/notifications'); const t = await p.locator('body').innerText();
    const ok = /\+256741141433/.test(t) && /2400725284@mubs\.ac\.ug/.test(t) && /Payment received/i.test(t); return [ok, ok ? 'SMS and “Payment received” email entries present in logs' : 'Entries not found']; });
  await tc('TC14', 'Admin publishes a travel update', 'As admin: Travel updates → New; fill form; save; open public Updates page', 'New alert appears on the public page', async () => {
    await go('/admin/travel-updates/create'); await p.selectOption('select[name=category]', 'ferry'); await p.selectOption('select[name=severity]', 'warning');
    await p.fill('input[name=title]', 'TEST: Ferry delayed 30 minutes'); await p.fill('textarea[name=body]', 'Test alert created during system testing.');
    await p.check('input[name=is_active]'); await Promise.all([p.waitForNavigation(), p.click('button:has-text("Save update")')]);
    await go('/updates'); const n = await p.locator('text=TEST: Ferry delayed 30 minutes').count(); return [n > 0, n ? 'Alert visible on /updates' : 'Alert not visible']; });
  fs.writeFileSync(`${out}/results.json`, JSON.stringify(results, null, 1)); await b.close();
})();
