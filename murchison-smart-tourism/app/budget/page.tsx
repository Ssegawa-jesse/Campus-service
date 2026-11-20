import type { Metadata } from 'next';
import BudgetCalculator from '@/components/BudgetCalculator';

export const metadata: Metadata = { title: 'Travel budget' };

export default function BudgetPage() {
  return (
    <>
      <section className="page-head">
        <div className="container">
          <div className="eyebrow">Travel budgeting</div>
          <h1>Estimate your trip expenditure</h1>
          <p>Change the number of days, people, accommodation and activities to see what a visit to Murchison Falls is likely to cost, in US dollars and Uganda shillings.</p>
        </div>
      </section>
      <section className="section"><div className="container"><BudgetCalculator /></div></section>
    </>
  );
}
