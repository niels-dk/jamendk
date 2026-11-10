// public/js/track.js — a few counted moments on the public pages, for the
// "before sign-up" funnel in /admin/analytics.
//
// Loaded only for visitors who are NOT signed in. No cookie, nothing stored on
// the device, nothing sent to anyone but this site. Each call is a name and a
// short code ("cta" + "hero"); never anything typed. The server ties it to the
// same one-day fingerprint it already uses for page views (app/analytics.php).
//
// The point of doing this in a script at all: scanners and referrer spam make
// up most of the page views, and they do not run scripts. A "human" row is the
// difference between a visit and a request.
(function () {
  'use strict';
  if (navigator.webdriver) return;                 // automated browsers

  var path = location.pathname || '/';
  var sent = {};

  function send(name, detail) {
    var key = name + '|' + (detail || '');
    if (sent[key]) return;                         // once per page load
    sent[key] = 1;
    try {
      var body = new URLSearchParams({ n: name, d: detail || '', p: path });
      if (navigator.sendBeacon) navigator.sendBeacon('/api/a', body);
      else fetch('/api/a', { method: 'POST', body: body, keepalive: true, credentials: 'same-origin' });
    } catch (e) { /* counting must never break a page */ }
  }

  // A real person: the first touch, key or scroll — or the page simply being
  // looked at for a couple of seconds.
  function human() { send('human'); }
  ['pointerdown', 'keydown', 'touchstart', 'wheel'].forEach(function (ev) {
    window.addEventListener(ev, human, { once: true, passive: true });
  });
  setTimeout(function () { if (document.visibilityState === 'visible') human(); }, 2500);

  // How far down the front page. The page's scroller is <body> on this site,
  // so listen in the capture phase (scroll does not bubble) and measure both.
  if (path === '/') {
    var onScroll = function () {
      human();
      var el = document.scrollingElement || document.documentElement;
      var b = document.body;
      var top = Math.max(el.scrollTop, b.scrollTop, window.scrollY || 0);
      var full = Math.max(el.scrollHeight, b.scrollHeight);
      var vh = window.innerHeight || 1;
      if (top > vh * 0.6) send('scroll', 'fold');          // past the first screen
      if (full > vh) {
        var seen = (top + vh) / full;
        if (seen >= 0.5) send('scroll', 'half');
        if (seen >= 0.9) send('scroll', 'end');
      }
    };
    document.addEventListener('scroll', onScroll, { capture: true, passive: true });
  }

  // Which sign-up button, and the example Trip link.
  document.addEventListener('click', function (e) {
    var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
    if (!a) return;
    var href = (a.getAttribute('href') || '').split('?')[0];
    if (href === '/register') {
      send('cta', a.closest('.home-header') ? 'header'
                : a.closest('.lp-hero')     ? 'hero'
                : a.closest('.lp-close')    ? 'bottom'
                : a.closest('.site-footer') ? 'footer' : 'other');
    } else if (href.indexOf('/t/') === 0) {
      send('demo_trip');
    }
  }, true);

  // The sign-up form: started, and sent. (Whether it was accepted, and why
  // not, is recorded by the server — it is the only one that knows.)
  if (path === '/register') {
    var form = document.querySelector('form.auth-form');
    if (form) {
      form.addEventListener('input', function () { send('form_start'); }, { once: true });
      form.addEventListener('submit', function () { send('form_submit'); });
    }
  }
})();
