(function () {
  'use strict';

  var tableSelector = [
    '.kasuari-table-wrap table',
    '.w3-responsive table',
    '#results_content > table',
    '#tampilan > table'
  ].join(',');

  function normalizeLabel(value) {
    return String(value || '').replace(/\s+/g, ' ').trim();
  }

  function enhanceTable(table) {
    if (!table || table.dataset.mobileReady === 'true') return;

    var headers = Array.prototype.map.call(
      table.querySelectorAll('thead th, thead td'),
      function (header) { return normalizeLabel(header.textContent); }
    );

    if (!headers.length) return;

    Array.prototype.forEach.call(table.querySelectorAll('tbody tr'), function (row) {
      Array.prototype.forEach.call(row.children, function (cell, index) {
        if (cell.tagName === 'TD' && !cell.hasAttribute('data-label')) {
          cell.setAttribute('data-label', headers[index] || 'Data');
        }
      });
    });

    table.classList.add('kasuari-responsive-data');
    table.dataset.mobileReady = 'true';
  }

  function scanTables(root) {
    if (!root || !root.querySelectorAll) return;
    if (root.matches && root.matches(tableSelector)) enhanceTable(root);
    Array.prototype.forEach.call(root.querySelectorAll(tableSelector), enhanceTable);
  }

  function refreshDynamicTable(table) {
    if (!table) return;
    table.dataset.mobileReady = 'false';
    enhanceTable(table);
  }

  document.addEventListener('DOMContentLoaded', function () {
    scanTables(document);

    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        var table = mutation.target.closest ? mutation.target.closest('table') : null;
        if (table && table.matches(tableSelector)) refreshDynamicTable(table);
        Array.prototype.forEach.call(mutation.addedNodes, function (node) {
          if (node.nodeType === 1) scanTables(node);
        });
      });
    });

    observer.observe(document.body, { childList: true, subtree: true });
  });
}());
