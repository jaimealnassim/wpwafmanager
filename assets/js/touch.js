/**
 * Cloudflare WAF Manager — Touch & Swipe Utilities
 * Provides swipe detection and scroll-snap for tab bars.
 */
(function () {
  'use strict';

  /* ── Swipe detector ──────────────────────────────────────────────────────── */

  /**
   * Attach swipe detection to an element.
   * @param {Element} el        Element to watch.
   * @param {Function} onSwipe  Called with 'left' or 'right'.
   * @param {number} threshold  Minimum px to count as a swipe (default 50).
   */
  function addSwipe(el, onSwipe, threshold) {
    threshold = threshold || 50;
    var startX, startY, startTime;

    el.addEventListener('touchstart', function (e) {
      startX    = e.touches[0].clientX;
      startY    = e.touches[0].clientY;
      startTime = Date.now();
    }, { passive: true });

    el.addEventListener('touchend', function (e) {
      var dx = e.changedTouches[0].clientX - startX;
      var dy = e.changedTouches[0].clientY - startY;
      var dt = Date.now() - startTime;

      // Must be fast enough (<400ms), wide enough (>threshold), and more horizontal than vertical
      if (dt < 400 && Math.abs(dx) > threshold && Math.abs(dx) > Math.abs(dy) * 1.5) {
        onSwipe(dx > 0 ? 'right' : 'left');
      }
    }, { passive: true });
  }

  /* ── Tab swiper ──────────────────────────────────────────────────────────── */

  /**
   * Make a set of tabs swipeable on a content container.
   * @param {Object} opts
   *   tabs       — NodeList/Array of tab <button> elements.
   *   panels     — NodeList/Array of content panel elements.
   *   swipeEl    — Element to attach swipe listener to (usually the panel wrapper).
   *   dotsEl     — Optional element to render indicator dots into.
   *   onChange   — Optional callback(index) fired when tab changes.
   */
  function TabSwiper(opts) {
    var tabs    = Array.from(opts.tabs);
    var panels  = Array.from(opts.panels);
    var current = tabs.findIndex(function (t) { return t.classList.contains('active'); });
    if (current < 0) current = 0;

    function go(index) {
      if (index < 0 || index >= tabs.length) return;
      tabs.forEach(function (t, i) {
        t.classList.toggle('active', i === index);
      });
      panels.forEach(function (p, i) {
        p.classList.toggle('active', i === index);
      });
      current = index;
      updateDots();
      // Scroll active tab into view in the tab bar
      tabs[index].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
      if (opts.onChange) opts.onChange(index);
    }

    // Wire existing tab click handlers to also update current index
    tabs.forEach(function (tab, i) {
      tab.addEventListener('click', function () { go(i); });
    });

    // Swipe on the content area
    if (opts.swipeEl) {
      addSwipe(opts.swipeEl, function (dir) {
        go(dir === 'left' ? current + 1 : current - 1);
      });
    }

    // Dot indicators
    var dots = [];
    function buildDots() {
      if (!opts.dotsEl) return;
      opts.dotsEl.innerHTML = '';
      tabs.forEach(function (_, i) {
        var dot = document.createElement('span');
        dot.className = 'cfwaf-swipe-dot' + (i === current ? ' active' : '');
        dot.addEventListener('click', function () { go(i); });
        opts.dotsEl.appendChild(dot);
        dots.push(dot);
      });
    }

    function updateDots() {
      dots.forEach(function (d, i) { d.classList.toggle('active', i === current); });
    }

    buildDots();

    return { go: go, current: function () { return current; } };
  }

  /* ── Scrollable tab bar momentum fix ─────────────────────────────────────── */

  /**
   * Makes a tab bar scroll the active tab into view when the page loads.
   */
  function scrollActiveTabIntoView(tabBar) {
    var active = tabBar.querySelector('.active, [aria-selected="true"]');
    if (active) {
      setTimeout(function () {
        active.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
      }, 100);
    }
  }

  /* ── WAF Rules main tabs ──────────────────────────────────────────────────── */

  function initWAFTabs() {
    var tabBar  = document.getElementById('cfwaf-main-tabs');
    var tabs    = tabBar && tabBar.querySelectorAll('.cfwaf-tab');
    var panels  = document.querySelectorAll('.cfwaf-tab-content');
    var dotsEl  = document.getElementById('cfwaf-main-dots');
    if (!tabBar || !tabs || !tabs.length || !panels.length) return;

    // Wrap all tab panels for swipe detection
    var contentWrap = panels[0] && panels[0].parentElement;

    new TabSwiper({
      tabs:     tabs,
      panels:   panels,
      swipeEl:  contentWrap || document.querySelector('.cfwaf-wrap'),
      dotsEl:   dotsEl,
    });

    scrollActiveTabIntoView(tabBar);
  }

  /* ── IP Rules filter tabs ─────────────────────────────────────────────────── */

  function initIPFilterTabs() {
    var tabBar = document.querySelector('.cfwaf-ip-filter-tabs');
    if (!tabBar) return;
    scrollActiveTabIntoView(tabBar);

    // Swipe on table area to switch filter tabs
    var tabs   = Array.from(tabBar.querySelectorAll('.cfwaf-ip-filter-tab'));
    var table  = document.querySelector('.cfwaf-ip-table-wrap');
    if (!table || !tabs.length) return;

    addSwipe(table, function (dir) {
      var current = tabs.findIndex(function (t) { return t.classList.contains('active'); });
      var next    = dir === 'left' ? current + 1 : current - 1;
      if (next >= 0 && next < tabs.length) {
        tabs[next].click();
        tabs[next].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
      }
    });
  }

  /* ── DNS type filter ─────────────────────────────────────────────────────── */

  function initDNSSwipe() {
    var wrap = document.querySelector('.cfwaf-dns-table-wrap');
    if (!wrap) return;

    // Remove scroll-hint gradient once user scrolls right
    wrap.addEventListener('scroll', function () {
      if (wrap.scrollLeft > 20) {
        wrap.classList.add('scrolled-right');
      } else {
        wrap.classList.remove('scrolled-right');
      }
    }, { passive: true });

    // Touch-momentum swipe: track finger and scroll the wrapper
    var startX, startScrollLeft, isDragging = false;

    wrap.addEventListener('touchstart', function (e) {
      startX          = e.touches[0].clientX;
      startScrollLeft = wrap.scrollLeft;
      isDragging      = true;
    }, { passive: true });

    wrap.addEventListener('touchmove', function (e) {
      if (!isDragging) return;
      var dx = startX - e.touches[0].clientX;
      wrap.scrollLeft = startScrollLeft + dx;
    }, { passive: true });

    wrap.addEventListener('touchend', function () {
      isDragging = false;
    }, { passive: true });
  }

  /* ── Zone status cards ───────────────────────────────────────────────────── */

  function initZoneStatusSwipe() {
    var grid = document.getElementById('cfwaf-zs-cards');
    if (!grid) return;
    // Cards stack vertically — no horizontal swipe needed
    // Swipe on settings bar to quickly open/close zone picker
    var picker = document.getElementById('cfwaf-zs-zone-picker-toggle');
    if (picker) {
      addSwipe(picker, function (dir) {
        if (dir === 'down') picker.click();
      }, 30);
    }
  }

  /* ── Boot ────────────────────────────────────────────────────────────────── */

  document.addEventListener('DOMContentLoaded', function () {
    initWAFTabs();
    initIPFilterTabs();
    initDNSSwipe();
    initZoneStatusSwipe();
  });

  // Expose for external use if needed
  window.CfWafTouch = { addSwipe: addSwipe, TabSwiper: TabSwiper };

})();
