/**
 * Google Analytics & event tracking
 * Replace GA_MEASUREMENT_ID with your GA4 Measurement ID (e.g. G-XXXXXXXXXX)
 */
(function () {
  'use strict';
  var GA_ID = 'GA_MEASUREMENT_ID'; // Replace with your GA4 ID

  function loadGA() {
    if (typeof window.gtag === 'function' || !GA_ID || GA_ID === 'GA_MEASUREMENT_ID') return;
    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + GA_ID;
    document.head.appendChild(s);
    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push(arguments); }
    window.gtag = gtag;
    gtag('js', new Date());
    gtag('config', GA_ID, { send_page_view: true });
  }

  function trackEvent(action, category, label, value) {
    if (typeof window.gtag === 'function') {
      window.gtag('event', action, {
        event_category: category || 'engagement',
        event_label: label || undefined,
        value: value
      });
    }
  }

  function initTracking() {
    document.querySelectorAll('[data-track="call"]').forEach(function (el) {
      el.addEventListener('click', function () {
        trackEvent('click_call', 'contact', el.getAttribute('data-track-label') || 'call_button');
      });
    });
    document.querySelectorAll('[data-track="quote"]').forEach(function (el) {
      el.addEventListener('click', function () {
        trackEvent('click_quote', 'lead', el.getAttribute('data-track-label') || 'quote_button');
      });
    });
    document.querySelectorAll('[data-track="contact"]').forEach(function (el) {
      el.addEventListener('click', function () {
        trackEvent('click_contact', 'lead', el.getAttribute('data-track-label') || 'contact_form');
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      loadGA();
      initTracking();
    });
  } else {
    loadGA();
    initTracking();
  }
  window.trackEvent = trackEvent;
})();
