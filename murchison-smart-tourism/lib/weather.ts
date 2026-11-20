import { PARK } from './data';

export type DayWeather = { date: string; code: number; max: number; min: number; rain: number };

const CODES: [number[], string, string][] = [
  [[0], '☀️', 'Clear'], [[1, 2], '🌤️', 'Partly cloudy'], [[3], '☁️', 'Cloudy'], [[45, 48], '🌫️', 'Fog'],
  [[51, 53, 55, 56, 57], '🌦️', 'Drizzle'], [[61, 63, 65, 66, 67, 80, 81, 82], '🌧️', 'Rain'],
  [[71, 73, 75, 77, 85, 86], '🌨️', 'Snow'], [[95, 96, 99], '⛈️', 'Thunderstorms'],
];
export function describe(code: number) {
  const hit = CODES.find(([c]) => c.includes(code));
  return hit ? { icon: hit[1], label: hit[2] } : { icon: '🌡️', label: 'Weather' };
}

/** 16-day forecast for Paraa from Open-Meteo (free, no API key). Runs in the visitor's browser. */
export async function fetchForecast(): Promise<DayWeather[]> {
  const { lat, lng } = PARK.weatherPoint;
  const url = `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lng}` +
    '&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max&timezone=Africa%2FKampala&forecast_days=16';
  const res = await fetch(url);
  if (!res.ok) throw new Error('Weather service unavailable');
  const j = await res.json();
  const d = j.daily;
  return d.time.map((t: string, i: number) => ({
    date: t, code: d.weather_code[i], max: Math.round(d.temperature_2m_max[i]), min: Math.round(d.temperature_2m_min[i]),
    rain: d.precipitation_probability_max?.[i] ?? 0,
  }));
}
