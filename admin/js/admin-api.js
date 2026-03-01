/**
 * Admin API helpers – robust fetch with timeout, shared driver assignment.
 * Prevents infinite loading and ensures error/loading states are always resolved.
 */
(function(global) {
  var FETCH_TIMEOUT = 15000;

  function fetchWithTimeout(url, options) {
    var controller = new AbortController();
    var timeoutId = setTimeout(function() { controller.abort(); }, FETCH_TIMEOUT);
    var opts = options || {};
    opts.signal = controller.signal;
    opts.credentials = opts.credentials || 'same-origin';
    return fetch(url, opts).then(function(r) {
      clearTimeout(timeoutId);
      if (!r.ok) throw new Error('Server error ' + r.status);
      return r.json();
    }).catch(function(err) {
      clearTimeout(timeoutId);
      throw err;
    });
  }

  function loadDriversForAssign(ref, assignedDriverId, onAssignSuccess) {
    var sel = document.getElementById('assign-driver-select');
    if (!sel) return;
    fetchWithTimeout('api/drivers-list.php')
      .then(function(d) {
        var drivers = d.drivers || [];
        var connectFirst = drivers.filter(function(drv) { return drv.source === 'connect'; });
        var adminOnly = drivers.filter(function(drv) { return drv.source === 'admin'; });
        var allDrivers = connectFirst.concat(adminOnly);
        if (allDrivers.length === 0) {
          sel.innerHTML = '<option value="">No drivers – add drivers in Drivers or they appear after onboarding</option>';
        } else {
          sel.innerHTML = '<option value="">Assign driver…</option>' + allDrivers.map(function(drv) {
            var selAttr = (assignedDriverId && drv.id === assignedDriverId) ? ' selected' : '';
            var label = (drv.name || drv.email || drv.id) + (drv.van_make || drv.van_reg ? ' – ' + (drv.van_make || '') + ' ' + (drv.van_reg || '') : '');
            return '<option value="' + String(drv.id).replace(/"/g,'&quot;') + '"' + selAttr + '>' + label.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</option>';
          }).join('');
        }
        var btn = document.getElementById('assign-driver-btn');
        if (btn) btn.onclick = function() {
          var did = sel.value;
          if (!did) { alert('Select a driver first.'); return; }
          btn.disabled = true;
          btn.textContent = 'Assigning…';
          fetchWithTimeout('api/assign-driver.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ reference: ref, driver_id: did })
          }).then(function(res) {
            btn.disabled = false;
            btn.textContent = 'Assign';
            if (res.ok) {
              if (typeof onAssignSuccess === 'function') onAssignSuccess(ref);
            } else {
              alert(res.error || 'Failed to assign driver');
            }
          }).catch(function() {
            btn.disabled = false;
            btn.textContent = 'Assign';
            alert('Network error. Try again.');
          });
        };
      })
      .catch(function() {
        sel.innerHTML = '<option value="">Failed to load drivers</option>';
      });
  }

  global.AdminAPI = {
    fetchWithTimeout: fetchWithTimeout,
    loadDriversForAssign: loadDriversForAssign
  };
})(typeof window !== 'undefined' ? window : this);
