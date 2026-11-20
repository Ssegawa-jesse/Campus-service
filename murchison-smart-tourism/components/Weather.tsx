'use client';
import { useEffect, useState } from 'react';
import { DayWeather, describe, fetchForecast } from '@/lib/weather';
import { PARK } from '@/lib/data';

export default function Weather() {
  const [days, setDays] = useState<DayWeather[] | null>(null);
  const [error, setError] = useState('');
  useEffect(() => { fetchForecast().then(setDays).catch(() => setError('The live forecast could not be loaded right now. Check your connection and try again later.')); }, []);

  if (error) return <div className="notice warn">{error}</div>;
  if (!days) return <p className="muted">Loading the forecast for {PARK.weatherPoint.label}…</p>;
  const week = days.slice(0, 7);
  const rainyDays = days.filter(d => d.rain >= 60).length;
  return (
    <>
      <div className="forecast">
        {week.map(d => {
          const w = describe(d.code);
          return (
            <div className="day-w" key={d.date}>
              <div className="small muted">{new Date(d.date + 'T00:00:00').toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric' })}</div>
              <div className="ico" aria-hidden>{w.icon}</div>
              <div className="small">{w.label}</div>
              <strong>{d.max}°</strong> <span className="muted small">{d.min}°</span>
              <div className="small" style={{ color: 'var(--nile)' }}>💧 {d.rain}%</div>
            </div>
          );
        })}
      </div>
      <p className="small muted" style={{ marginTop: 10 }}>
        Forecast for {PARK.weatherPoint.label}. {rainyDays > 0 ? `${rainyDays} of the next 16 days have a high chance of rain — pack a rain jacket.` : 'Little rain is expected over the next 16 days.'} Data: Open-Meteo.
      </p>
    </>
  );
}
