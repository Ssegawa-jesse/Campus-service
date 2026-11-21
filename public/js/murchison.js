/* Murchison Falls Smart Tourism – weather, sharing and the travel assistant chat. */
(function () {
  'use strict';

  // ---------- Weather (Open-Meteo, free, no key; runs in the visitor's browser) ----------
  var CODES = [[[0], '☀️', 'Clear'], [[1, 2], '🌤️', 'Partly cloudy'], [[3], '☁️', 'Cloudy'], [[45, 48], '🌫️', 'Fog'],
    [[51, 53, 55, 56, 57], '🌦️', 'Drizzle'], [[61, 63, 65, 66, 67, 80, 81, 82], '🌧️', 'Rain'], [[95, 96, 99], '⛈️', 'Thunderstorms']];
  function describe(code) {
    for (var i = 0; i < CODES.length; i++) if (CODES[i][0].indexOf(code) >= 0) return { icon: CODES[i][1], label: CODES[i][2] };
    return { icon: '🌡️', label: 'Weather' };
  }
  function forecast() {
    var p = window.MF_WEATHER;
    if (!p) return Promise.reject(new Error('no point'));
    var url = 'https://api.open-meteo.com/v1/forecast?latitude=' + p.lat + '&longitude=' + p.lng +
      '&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max&timezone=Africa%2FKampala&forecast_days=16';
    return fetch(url).then(function (r) { if (!r.ok) throw new Error('weather'); return r.json(); }).then(function (j) {
      var d = j.daily;
      return d.time.map(function (t, i) {
        return { date: t, code: d.weather_code[i], max: Math.round(d.temperature_2m_max[i]), min: Math.round(d.temperature_2m_min[i]), rain: (d.precipitation_probability_max || [])[i] || 0 };
      });
    });
  }

  var dayBadges = document.querySelectorAll('[data-wx-date]');
  var widget = document.getElementById('mf-forecast');
  if (dayBadges.length || widget) {
    forecast().then(function (days) {
      var byDate = {};
      days.forEach(function (d) { byDate[d.date] = d; });
      dayBadges.forEach(function (el) {
        var w = byDate[el.getAttribute('data-wx-date')];
        if (!w) return;
        var x = describe(w.code);
        el.textContent = x.icon + ' ' + w.min + '–' + w.max + '°C · ' + w.rain + '% rain';
        el.title = 'Forecast for ' + window.MF_WEATHER.label;
      });
      if (widget) {
        widget.innerHTML = days.slice(0, 7).map(function (w) {
          var x = describe(w.code);
          var label = new Date(w.date + 'T00:00:00').toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric' });
          return '<div class="col"><div class="card border-0 shadow-sm text-center h-100"><div class="card-body p-2 small">' +
            '<div class="text-muted">' + label + '</div><div class="fs-2">' + x.icon + '</div><div>' + x.label + '</div>' +
            '<strong>' + w.max + '°</strong> <span class="text-muted">' + w.min + '°</span><div class="text-primary">💧 ' + w.rain + '%</div></div></div></div>';
        }).join('');
        var rainy = days.filter(function (d) { return d.rain >= 60; }).length;
        var note = document.getElementById('mf-forecast-note');
        if (note) note.textContent = rainy ? rainy + ' of the next 16 days have a high chance of rain — pack a rain jacket.' : 'Little rain is expected over the next 16 days.';
      }
    }).catch(function () {
      if (widget) widget.innerHTML = '<div class="col-12"><div class="alert alert-warning mb-0">The live forecast could not be loaded right now. Check your internet connection and try again later.</div></div>';
    });
  }

  // ---------- Share an itinerary ----------
  document.querySelectorAll('[data-share]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var url = btn.getAttribute('data-share');
      if (navigator.share) { navigator.share({ title: 'My Murchison Falls itinerary', url: url }).catch(function () {}); return; }
      if (navigator.clipboard) navigator.clipboard.writeText(url).then(function () { btn.innerHTML = '✓ Link copied'; });
      else window.prompt('Copy this link:', url);
    });
  });

  // ---------- Travel assistant chat ----------
  var chat = document.getElementById('mf-chat');
  if (chat) {
    var log = chat.querySelector('.chat-log');
    var form = chat.querySelector('form');
    var input = form.querySelector('input');
    var sendBtn = form.querySelector('button');
    var history = [];
    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    function add(role, text, source) {
      var div = document.createElement('div');
      div.className = 'msg ' + (role === 'user' ? 'user' : 'bot');
      div.textContent = text;
      if (source) { var s = document.createElement('span'); s.className = 'src'; s.textContent = source === 'ai' ? 'AI answer' : 'Answer from the park guide'; div.appendChild(s); }
      log.appendChild(div);
      log.scrollTop = log.scrollHeight;
      return div;
    }
    function ask(q) {
      q = (q || '').trim();
      if (!q || sendBtn.disabled) return;
      add('user', q);
      history.push({ role: 'user', content: q });
      input.value = '';
      sendBtn.disabled = true;
      var thinking = add('bot', 'Thinking…');
      fetch(chat.getAttribute('data-url'), {
        method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ messages: history.slice(-12) })
      }).then(function (r) { return r.json(); }).then(function (d) {
        thinking.remove();
        var reply = d.reply || 'Sorry, something went wrong.';
        add('assistant', reply, d.source);
        history.push({ role: 'assistant', content: reply });
      }).catch(function () {
        thinking.remove();
        add('assistant', 'Sorry, I could not reach the server. Check your connection and try again.');
        history.pop();
      }).finally(function () { sendBtn.disabled = false; input.focus(); });
    }
    form.addEventListener('submit', function (e) { e.preventDefault(); ask(input.value); });
    chat.querySelectorAll('[data-q]').forEach(function (b) { b.addEventListener('click', function () { ask(b.getAttribute('data-q')); }); });
  }
})();
