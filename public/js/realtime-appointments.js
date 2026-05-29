/**
 * Live dashboard updates via lightweight polling (works on Railway + PHP built-in server).
 */
(function (global) {
    'use strict';

    const POLL_MS = 3000;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function flash(el) {
        if (!el) return;
        el.classList.remove('live-updated');
        void el.offsetWidth;
        el.classList.add('live-updated');
    }

    function fetchJson(url) {
        return fetch(url, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
            cache: 'no-store',
        }).then((res) => {
            if (!res.ok) throw new Error('Failed to load live data');
            return res.json();
        });
    }

    function setLiveBadge(active) {
        const badge = document.getElementById('live-sync-badge');
        if (!badge) return;
        badge.hidden = false;
        badge.classList.toggle('live-sync-badge--on', active);
        badge.textContent = active ? 'Live — updates automatically' : 'Live — reconnecting…';
    }

    /**
     * Polls a tiny version endpoint; calls onChange when the version changes.
     */
    function startVersionPolling(versionUrl, onChange) {
        if (!versionUrl) return () => {};

        let lastVersion = null;
        let stopped = false;

        const poll = () => {
            if (stopped) return;

            fetchJson(versionUrl)
                .then((data) => {
                    const version = data.version;
                    if (version === undefined || version === null) return;

                    setLiveBadge(true);

                    if (lastVersion === null) {
                        lastVersion = version;
                        return;
                    }

                    if (version !== lastVersion) {
                        lastVersion = version;
                        onChange();
                    }
                })
                .catch(() => setLiveBadge(false));
        };

        poll();
        const timer = global.setInterval(poll, POLL_MS);

        return () => {
            stopped = true;
            global.clearInterval(timer);
        };
    }

    function initStaffDashboard(versionUrl, dataUrl) {
        const root = document.getElementById('staff-dashboard-live');
        if (!root || !dataUrl || !versionUrl) return;

        const apply = (data) => {
            const stats = data.stats || {};
            root.querySelectorAll('[data-stat]').forEach((el) => {
                const key = el.getAttribute('data-stat');
                if (key in stats && el.textContent !== String(stats[key])) {
                    el.textContent = String(stats[key]);
                    flash(el.closest('.stat-card') || el);
                }
            });

            const grid = document.getElementById('status-counts-grid');
            if (grid && Array.isArray(data.trackedStatuses)) {
                data.trackedStatuses.forEach((status) => {
                    const pill = grid.querySelector('[data-status="' + status + '"] .num');
                    const count = (data.statusCounts && data.statusCounts[status]) ?? 0;
                    if (pill && pill.textContent !== String(count)) {
                        pill.textContent = String(count);
                        flash(pill.closest('.status-pill'));
                    }
                });
            }

            const upcomingRoot = document.getElementById('upcoming-appointments-live');
            if (!upcomingRoot) return;

            const rows = data.upcoming || [];
            if (rows.length === 0) {
                upcomingRoot.innerHTML =
                    '<div class="empty-state">No upcoming appointments from today onward.</div>';
                flash(upcomingRoot);
                return;
            }

            const body = rows
                .map(
                    (row) => `<tr data-appt-id="${row.id}">
                    <td>${escapeHtml(row.dateLabel)}</td>
                    <td>${escapeHtml(row.patientName)}</td>
                    <td>${escapeHtml(row.doctorName)}</td>
                    <td>${escapeHtml(row.serviceName)}</td>
                    <td><span class="badge-status ${escapeHtml(row.statusBadgeClass)}">${escapeHtml(row.status)}</span></td>
                </tr>`,
                )
                .join('');

            upcomingRoot.innerHTML = `<div class="dash-table-wrap">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Date &amp; time</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Service</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>${body}</tbody>
                </table>
            </div>`;
            flash(upcomingRoot.closest('.dash-panel') || upcomingRoot);
        };

        const refresh = () => fetchJson(dataUrl).then(apply).catch(() => {});

        startVersionPolling(versionUrl, refresh);
    }

    function statusColor(statusLower) {
        if (statusLower === 'pending') return '#92400e';
        if (statusLower === 'confirmed') return '#1e40af';
        if (statusLower === 'cancelled') return '#991b1b';
        return '#065f46';
    }

    function initStaffList(versionUrl, dataUrl, canManage) {
        const table = document.getElementById('appointmentTable');
        if (!table || !dataUrl || !versionUrl) return;

        const dataTableOptions = {
            pageLength: 5,
            lengthMenu: [5, 10, 20, 50],
            language: {
                search: '🔍 Search:',
                lengthMenu: 'Show _MENU_ appointments per page',
                info: 'Showing _START_ to _END_ of _TOTAL_ appointments',
                paginate: { next: '→', previous: '←' },
            },
        };

        const buildActions = (row) => {
            let html = '<a href="' + escapeHtml(row.showUrl) + '">View</a>';
            if (!canManage) return html;

            html += ' | <a href="' + escapeHtml(row.editUrl) + '">Edit</a>';

            if (row.isPending && row.confirmUrl && row.rejectUrl) {
                html +=
                    ' | <form method="post" action="' +
                    escapeHtml(row.confirmUrl) +
                    '" style="display:inline;">' +
                    '<input type="hidden" name="_token" value="' +
                    escapeHtml(row.confirmToken) +
                    '">' +
                    '<button type="submit" style="background:none;border:none;color:#16a34a;cursor:pointer;font-weight:600;padding:0;">Confirm</button>' +
                    '</form>' +
                    ' | <form method="post" action="' +
                    escapeHtml(row.rejectUrl) +
                    '" style="display:inline;">' +
                    '<input type="hidden" name="_token" value="' +
                    escapeHtml(row.rejectToken) +
                    '">' +
                    '<button type="submit" style="background:none;border:none;color:#dc2626;cursor:pointer;font-weight:600;padding:0;">Reject</button>' +
                    '</form>';
            }

            return html;
        };

        const apply = (data) => {
            const rows = data.appointments || [];
            const $ = global.jQuery;
            const hadDataTable = $ && $.fn.DataTable && $.fn.DataTable.isDataTable(table);

            if (hadDataTable) {
                $(table).DataTable().destroy();
            }

            const tbody = table.querySelector('tbody');
            if (!tbody) return;

            if (rows.length === 0) {
                table.style.display = 'none';
                let empty = document.getElementById('appointment-list-empty');
                if (!empty) {
                    empty = document.createElement('p');
                    empty.id = 'appointment-list-empty';
                    empty.className = 'no-records';
                    empty.textContent = '🚫 No appointments found.';
                    table.parentNode.insertBefore(empty, table);
                }
                empty.style.display = '';
                flash(empty);
                return;
            }

            const empty = document.getElementById('appointment-list-empty');
            if (empty) empty.style.display = 'none';
            table.style.display = '';

            tbody.innerHTML = rows
                .map(
                    (row) => `<tr data-appt-id="${row.id}">
                    <td>${row.id}</td>
                    <td>${escapeHtml(row.patientName)}</td>
                    <td>${escapeHtml(row.appointmentDate)}</td>
                    <td><span style="font-weight:600;text-transform:capitalize;color:${statusColor(row.statusLower)}">${escapeHtml(row.status)}</span></td>
                    <td>${escapeHtml(row.notes)}</td>
                    <td>${buildActions(row)}</td>
                </tr>`,
                )
                .join('');

            if ($ && $.fn.DataTable) {
                $(table).DataTable(dataTableOptions);
            }

            flash(table.closest('.dataTables_wrapper') || table);
        };

        const refresh = () => fetchJson(dataUrl).then(apply).catch(() => {});

        startVersionPolling(versionUrl, refresh);
    }

    function initPatientBookings(versionUrl, dataUrl) {
        const root = document.getElementById('my-bookings-live');
        if (!root || !dataUrl || !versionUrl) return;

        const apply = (data) => {
            const rows = data.appointments || [];

            if (rows.length === 0) {
                root.innerHTML = '<p class="empty">You have no booking requests yet.</p>';
                flash(root);
                return;
            }

            root.innerHTML = rows
                .map((appt) => {
                    const pendingHint =
                        appt.statusSlug === 'pending'
                            ? '<p class="booking-meta" style="color:#92400e;">Awaiting staff confirmation.</p>'
                            : '';
                    const confirmedHint =
                        appt.statusSlug === 'confirmed'
                            ? '<p class="directions-hint">✅ Confirmed — <a href="' +
                              escapeHtml(appt.directionsUrl) +
                              '">Get directions to the clinic</a></p>'
                            : '';
                    const notes = appt.notes
                        ? '<p class="booking-meta"><strong>Notes:</strong> ' + escapeHtml(appt.notes) + '</p>'
                        : '';

                    return `<article class="booking-card" data-appt-id="${appt.id}">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
                        <h3>${escapeHtml(appt.serviceName)}</h3>
                        <span class="status-badge status-${escapeHtml(appt.statusSlug)}">${escapeHtml(appt.status)}</span>
                    </div>
                    <p class="booking-meta"><strong>Doctor:</strong> ${escapeHtml(appt.doctorName)}</p>
                    <p class="booking-meta"><strong>When:</strong> ${escapeHtml(appt.dateLabel)}</p>
                    ${notes}
                    ${pendingHint}
                    ${confirmedHint}
                </article>`;
                })
                .join('');

            flash(root);
        };

        const refresh = () => fetchJson(dataUrl).then(apply).catch(() => {});

        startVersionPolling(versionUrl, refresh);
    }

    global.HealthCareRealtime = {
        initStaffDashboard,
        initStaffList,
        initPatientBookings,
    };
})(window);
