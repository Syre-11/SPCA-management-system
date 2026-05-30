/**
 * Page-specific rendering and form handlers for static build.
 */
(function (global) {
  const store = () => global.spcaStore;
  const auth = () => global.SpcaAuth;

  function pageName() {
    const path = decodeURIComponent(location.pathname);
    return path.split('/').pop().replace(/\.html?$/i, '').toLowerCase();
  }

  function qs() {
    return Object.fromEntries(new URLSearchParams(location.search));
  }

  function esc(s) {
    const d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function fmtDate(d) {
    if (!d) return '';
    try {
      return new Date(d).toLocaleDateString('en-ZA', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
      });
    } catch {
      return d;
    }
  }

  function fillText(selector, value) {
    document.querySelectorAll(selector).forEach((el) => {
      el.textContent = value;
    });
  }

  function renderTableBody(tbody, rows, rowHtml) {
    if (!tbody) return;
    if (!rows.length) {
      tbody.innerHTML =
        '<tr><td colspan="20" class="empty-state">No records found.</td></tr>';
      return;
    }
    tbody.innerHTML = rows.map(rowHtml).join('');
  }

  async function initLoginUser() {
    const err = document.getElementById('spca-login-error');
    if (!err) {
      const p = document.querySelector('.login-container p');
      if (p) {
        const e = document.createElement('p');
        e.id = 'spca-login-error';
        e.style.color = 'red';
        p.after(e);
      }
    }
    const hint = document.createElement('div');
    hint.className = 'spca-demo-hint';
    hint.style.cssText =
      'margin-top:16px;padding:12px;background:#e8f5f3;border-radius:8px;font-size:13px;text-align:left;';
    hint.innerHTML =
      '<strong>Demo logins</strong> (pre-filled):<br>' +
      'Admin — <code>admin</code> / <code>Admin123!</code><br>' +
      'Vet — <code>drsmith</code> / <code>Vet123!</code><br>' +
      'Volunteer — <code>volunteer1</code> / <code>Vol123!</code>';
    const form = document.querySelector('.login-container form');
    if (form && !document.querySelector('.spca-demo-hint')) {
      form.parentElement.appendChild(hint);
      const user = form.querySelector('[name="username"]');
      const pass = form.querySelector('[name="password"]');
      const pos = form.querySelector('[name="position"]');
      if (user) user.value = 'admin';
      if (pass) pass.value = 'Admin123!';
      if (pos) pos.value = 'Administrative Staff';
    }
  }

  async function initAdminDashboard() {
    auth().requireRole(['Administrative Staff']);
    const stats = store().adminDashboardStats();
    fillText('[data-spca-stat="totalAnimals"]', stats.totalAnimals);
    fillText('[data-spca-stat="openReports"]', stats.openReports);
    fillText('[data-spca-stat="pendingAdoptions"]', stats.pendingAdoptions);
    fillText(
      '[data-spca-stat="donations"]',
      'R ' + Number(stats.donations).toFixed(2)
    );
    const list = document.querySelector('[data-spca-list="recentActivity"]');
    if (list) {
      list.innerHTML = stats.recentActivity.length
        ? stats.recentActivity
            .map(
              (a) =>
                `<li>🔹 ${esc(a.type)} – Ref #${esc(a.ref)} (${fmtDate(a.date)})</li>`
            )
            .join('')
        : '<li>No recent activity.</li>';
    }
    document.querySelectorAll('.cards .card p').forEach((p, i) => {
      const vals = [
        stats.totalAnimals,
        stats.openReports,
        stats.pendingAdoptions,
        'R ' + Number(stats.donations).toFixed(2),
      ];
      if (vals[i] != null) p.textContent = vals[i];
    });
    const ul = document.querySelector('.recent-activity ul');
    if (ul && !list) {
      ul.innerHTML = stats.recentActivity.length
        ? stats.recentActivity
            .map(
              (a) =>
                `<li>🔹 ${esc(a.type)} – Ref #${esc(a.ref)} (${fmtDate(a.date)})</li>`
            )
            .join('')
        : '<li>No recent activity.</li>';
    }
  }

  async function initVetDashboard() {
    auth().requireRole(['Veterinary Staff']);
    await store().ready;
    const animals = store().table('animal');
    const records = store().table('medicalrecords');
    const vets = store().table('veterinarians').filter((v) => v.is_active);
    const pending = records.filter((r) => r.status === 'Pending' && !r.Hide);

    fillText('[data-spca-stat="animalCount"]', animals.length);
    fillText('[data-spca-stat="vetCount"]', vets.length);

    if (window.Chart && document.getElementById('procedureChart')) {
      const procMap = {};
      records.forEach((r) => {
        procMap[r.procedure_type] = (procMap[r.procedure_type] || 0) + 1;
      });
      new Chart(document.getElementById('procedureChart'), {
        type: 'pie',
        data: {
          labels: Object.keys(procMap),
          datasets: [
            {
              data: Object.values(procMap),
              backgroundColor: ['#4CAF50', '#2196F3', '#FF9800', '#9C27B0'],
            },
          ],
        },
      });
    }
  }

  async function initVolunteerDashboard() {
    auth().requireRole(['Volunteer Staff']);
    const apps = store().table('VolunteerApplication');
    const hours = store().table('VolunteerHours');
    const now = new Date();
    const newApps = apps.filter((a) => a.Status === 'Pending').length;
    const approved = apps.filter(
      (a) => a.Status === 'Approved' && !a.IsHidden
    ).length;
    const monthHours = hours
      .filter((h) => {
        if (!h.Verified) return false;
        const d = new Date(h.DatePerformed);
        return (
          d.getMonth() === now.getMonth() &&
          d.getFullYear() === now.getFullYear()
        );
      })
      .reduce((s, h) => s + Number(h.Hours || 0), 0);

    document.querySelectorAll('.stat-card h2, .card-value').forEach(() => {});
    const cards = document.querySelectorAll('.dashboard-stats .stat-value, .stat-number, .cards .card p');
    const vals = [newApps, approved, monthHours];
    document.querySelectorAll('.cards .card p, .stat-box p, .stats-grid .value').forEach((el, i) => {
      if (vals[i] != null && i < 3) el.textContent = vals[i];
    });
  }

  async function initDisplayAnimals() {
    const params = qs();
    const animals = store().filterAnimals(params);
    const tbody = document.querySelector('table tbody');
    renderTableBody(tbody, animals, (a) => {
      const base = auth().abs('animal_intake_system/');
      return `<tr>
        <td>${esc(a.Animal_ID)}</td>
        <td>${esc(a.Animal_Name)}</td>
        <td>${esc(a.Animal_Species)}</td>
        <td>${esc(a.Animal_Breed)}</td>
        <td>${esc(a.Animal_Age)}</td>
        <td>${esc(a.Animal_Gender)}</td>
        <td>${esc(a.Animal_Size)}</td>
        <td>${esc(a.Animal_Health)}</td>
        <td>${fmtDate(a.Animal_Arrival_Date)}</td>
        <td>${esc(a.Animal_AdoptionStatus)}</td>
        <td>${esc(a.Kennel_ID || '')}</td>
        <td>
          <a href="${base}update_animal.html?id=${a.Animal_ID}">Edit</a>
          <a href="#" data-spca-delete-animal="${a.Animal_ID}" class="btn-delete">Delete</a>
        </td>
      </tr>`;
    });
    document.querySelectorAll('[data-spca-delete-animal]').forEach((a) => {
      a.addEventListener('click', (e) => {
        e.preventDefault();
        if (confirm('Delete this animal?')) {
          store().deleteAnimal(a.dataset.spcaDeleteAnimal);
          location.reload();
        }
      });
    });
    populateSelect('Animal_Species', store().distinctAnimalField('Animal_Species'), params.Animal_Species);
    populateSelect('Animal_Breed', store().distinctAnimalField('Animal_Breed'), params.Animal_Breed);
  }

  function populateSelect(name, options, selected) {
    const sel = document.querySelector(`[name="${name}"]`);
    if (!sel || sel.tagName !== 'SELECT') return;
    const current = sel.value;
    sel.innerHTML =
      `<option value="">All</option>` +
      options.map((o) => `<option value="${esc(o)}">${esc(o)}</option>`).join('');
    if (selected) sel.value = selected;
    else if (current) sel.value = current;
  }

  async function initDisplayUsers() {
    auth().requireRole(['Administrative Staff']);
    const search = (qs().search || '').trim().toLowerCase();
    let users = store().table('systemuser');
    if (search) {
      users = users.filter((u) =>
        (u.username || '').toLowerCase().includes(search)
      );
    }
    const tbody = document.querySelector('table tbody');
    renderTableBody(tbody, users, (u) => {
      const base = auth().abs('registerUser/');
      return `<tr>
        <td>${esc(u.SystemUser_ID)}</td>
        <td>${esc(u.username)}</td>
        <td>${esc(u.firstname)}</td>
        <td>${esc(u.surname)}</td>
        <td>${esc(u.email)}</td>
        <td>${esc(u.position)}</td>
        <td>
          <a href="${base}edit_user.html?id=${u.SystemUser_ID}">Edit</a>
          <a href="#" data-spca-delete-user="${u.SystemUser_ID}">Delete</a>
        </td>
      </tr>`;
    });
    document.querySelectorAll('[data-spca-delete-user]').forEach((a) => {
      a.addEventListener('click', (e) => {
        e.preventDefault();
        if (confirm('Delete user?')) {
          store().deleteUser(a.dataset.spcaDeleteUser);
          location.reload();
        }
      });
    });
  }

  async function initDonationSite() {
    const rows = store().table('alldonations');
    const total = rows.reduce((s, d) => s + Number(d.Amount || 0), 0);
    const donors = new Set(rows.map((d) => d.donor_id)).size;
    document.querySelectorAll('[data-spca-donation-total]').forEach((el) => {
      el.textContent = Number(total).toFixed(2);
    });
    document.querySelectorAll('[data-spca-donor-count]').forEach((el) => {
      el.textContent = donors;
    });
    const statEls = document.querySelectorAll('.stats-number, .total-amount');
    if (statEls[0]) statEls[0].textContent = 'R ' + Number(total).toFixed(2);
    if (statEls[1]) statEls[1].textContent = donors;
  }

  async function initAdoption() {
    const animals = store()
      .table('animal')
      .filter(
        (a) =>
          a.Animal_AdoptionStatus === 'Available' &&
          ['Excellent', 'Good'].includes(a.Animal_Health)
      );
    const select = document.querySelector('select[name="animal"], #animal');
    if (select) {
      select.innerHTML = animals
        .map(
          (a) =>
            `<option value="${esc(a.Animal_Name)}">${esc(a.Animal_Name)} (${esc(a.Animal_Breed)})</option>`
        )
        .join('');
    }
    const form = document.querySelector('form');
    if (form) {
      form.addEventListener('submit', (e) => {
        const fd = new FormData(form);
        if (fd.get('submit_application') != null || form.querySelector('[name="first_name"]')) {
          if (!fd.get('submit_application') && !form.querySelector('[name="check_status"]')) return;
        }
        if (fd.get('check_status') != null) {
          e.preventDefault();
          const input = fd.get('input') || fd.get('application_id');
          const app = store().findAdoptionApplication(String(input || '').trim());
          const out = document.getElementById('status-result') || document.querySelector('.status-result');
          if (out) {
            out.innerHTML = app
              ? `<p>Application #${app.Application_ID}: <strong>${esc(app.Application_Status)}</strong> (${fmtDate(app.Application_Date)}) — ${esc(app.Animal_Name)}</p>`
              : '<p>No application found.</p>';
          }
          return;
        }
        if (form.querySelector('[name="first_name"]') && e.submitter?.name !== 'check_status') {
          e.preventDefault();
          try {
            const id = store().addAdoptionApplication({
              First_Name: fd.get('first_name'),
              Last_Name: fd.get('last_name'),
              Email: fd.get('email'),
              Phone: fd.get('phone'),
              Animal_Name: fd.get('animal'),
              Address: fd.get('address'),
              Experience: fd.get('experience'),
            });
            alert(`Application submitted! Application ID: ${id}`);
            form.reset();
          } catch (err) {
            alert(err.message);
          }
        }
      });
    }
  }

  async function initCrueltyManagement() {
    const animals = store().table('animal');
    const staff = store().table('systemuser');
    const animalSel = document.querySelector('[name="FK_Animal_ID"], select[name*="Animal"]');
    if (animalSel) {
      animalSel.innerHTML =
        '<option value="">Select animal (optional)</option>' +
        animals
          .map(
            (a) =>
              `<option value="${a.Animal_ID}">${esc(a.Animal_ID)} - ${esc(a.Animal_Name)}</option>`
          )
          .join('');
    }
    const staffSel = document.querySelector('[name="FK_SystemUser_ID"]');
    if (staffSel) {
      staffSel.innerHTML = staff
        .map(
          (s) =>
            `<option value="${s.SystemUser_ID}">${esc(s.firstname)} ${esc(s.surname)}</option>`
        )
        .join('');
    }
    const form = document.querySelector('form[method="post"], form');
    if (form) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        const fd = new FormData(form);
        const id = store().addCrueltyReport(
          {
            ReportDate: fd.get('ReportDate'),
            Description: fd.get('Description'),
            Location: fd.get('Location'),
            FK_Animal_ID: fd.get('FK_Animal_ID'),
            FK_SystemUser_ID: Number(fd.get('FK_SystemUser_ID') || 1),
            Urgent: fd.get('Urgent') === 'on' || fd.get('Urgent') === '1',
          },
          {
            FirstName: fd.get('FirstName'),
            Surname: fd.get('Surname'),
            CellNumber: fd.get('CellNumber'),
            Email: fd.get('Email'),
          }
        );
        alert(`Report submitted. Your Report ID is: ${id}`);
        form.reset();
      });
    }
  }

  async function initViewAllReports() {
    auth().requireRole(['Administrative Staff', 'Veterinary Staff']);
    let reports = store().table('crueltyreport');
    const params = qs();
    if (params.status && params.status !== 'all') {
      reports = reports.filter((r) => r.Status === params.status);
    }
    if (params.show_deleted === 'deleted') {
      reports = reports.filter((r) => r.deleted);
    } else if (params.show_deleted !== 'all') {
      reports = reports.filter((r) => !r.deleted);
    }
    const tbody = document.querySelector('table tbody');
    renderTableBody(tbody, reports, (r) => {
      const animal = store()
        .table('animal')
        .find((a) => String(a.Animal_ID) === String(r.FK_Animal_ID));
      return `<tr>
        <td>${esc(r.Report_ID)}</td>
        <td>${esc(animal?.Animal_Name || '—')}</td>
        <td>${esc(r.Status)}</td>
        <td>${r.Urgent ? 'Yes' : 'No'}</td>
        <td>${fmtDate(r.ReportDate)}</td>
        <td><a href="ViewReport.html?id=${r.Report_ID}">View</a></td>
      </tr>`;
    });
  }

  async function initDisplayAllDonors() {
    let rows = [...store().table('alldonations')];
    const params = qs();
    if (params.search) {
      const s = params.search.toLowerCase();
      rows = rows.filter(
        (d) =>
          (d.name || '').toLowerCase().includes(s) ||
          (d.surname || '').toLowerCase().includes(s) ||
          (d.email || '').toLowerCase().includes(s)
      );
    }
    rows.sort((a, b) =>
      (b.DonationDate || '').localeCompare(a.DonationDate || '')
    );
    const tbody = document.querySelector('table tbody');
    renderTableBody(tbody, rows, (d) => `<tr>
      <td>${esc(d.donor_id)}</td>
      <td>${esc(d.name)} ${esc(d.surname)}</td>
      <td>${esc(d.email)}</td>
      <td>R ${Number(d.Amount).toFixed(2)}</td>
      <td>${fmtDate(d.DonationDate)}</td>
      <td>${esc(d.PaymentMethod)}</td>
    </tr>`);
  }

  async function initMedicalDisplay() {
    auth().requireRole(['Veterinary Staff', 'Administrative Staff']);
    const params = qs();
    let rows = store().table('medicalrecords').filter((r) => !r.Hide);
    if (params.search_animal_id) {
      rows = rows.filter(
        (r) => String(r.Animal_ID) === String(params.search_animal_id)
      );
    }
    if (params.archive_id) {
      store().archiveMedicalRecord(params.archive_id, true);
      location.href = location.pathname;
      return;
    }
    if (params.recover_id) {
      store().archiveMedicalRecord(params.recover_id, false);
      location.href = location.pathname;
      return;
    }
    const tbody = document.querySelector('table tbody');
    renderTableBody(tbody, rows, (r) => {
      const name = store().joinAnimalName(r.Animal_ID);
      return `<tr>
        <td>${esc(r.id)}</td>
        <td>${esc(r.Animal_ID)} (${esc(name)})</td>
        <td>${esc(r.procedure_type)}</td>
        <td>${fmtDate(r.procedure_date)}</td>
        <td>${esc(r.status)}</td>
        <td>R ${Number(r.cost || 0).toFixed(2)}</td>
        <td><a href="?archive_id=${r.id}">Archive</a></td>
      </tr>`;
    });
  }

  async function initAnimalIntakeSite() {
    const animals = store().table('animal');
    fillText('[data-spca-next-id]', animals.length ? Math.max(...animals.map((a) => a.Animal_ID)) + 1 : 1);
    fillText('[data-spca-total-animals]', animals.length);
    const form = document.querySelector('form');
    if (form && form.querySelector('[name="Animal_Name"]')) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        const fd = new FormData(form);
        store().addAnimal({
          Animal_Name: fd.get('Animal_Name'),
          Animal_Species: fd.get('Animal_Species'),
          Animal_Breed: fd.get('Animal_Breed'),
          Animal_Age: Number(fd.get('Animal_Age')),
          Animal_Gender: fd.get('Animal_Gender'),
          Animal_Size: fd.get('Animal_Size'),
          Animal_Health: fd.get('Animal_Health'),
          Animal_Arrival_Date: fd.get('Animal_Arrival_Date'),
          Animal_AdoptionStatus: fd.get('Animal_AdoptionStatus') || 'Available',
          Kennel_ID: fd.get('Kennel_ID') || null,
        });
        alert('Animal record created.');
        location.href = auth().abs('animal_intake_system/display_animals.html');
      });
    }
  }

  async function initRegisterForm() {
    const form = document.querySelector('form');
    if (!form) return;
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const fd = new FormData(form);
      if (fd.get('password') !== fd.get('confirm_password')) {
        alert('Passwords do not match');
        return;
      }
      try {
        store().addUser({
          username: fd.get('username'),
          firstname: fd.get('firstname'),
          surname: fd.get('surname'),
          dateOfBirth: fd.get('dateOfBirth') || null,
          email: fd.get('email'),
          password: fd.get('password'),
          phone: fd.get('phone'),
          gender: fd.get('gender'),
          position: fd.get('position'),
        });
        alert('User created! You can log in.');
        location.href = auth().abs('registerUser/LoginUser.html');
      } catch (err) {
        alert(err.message);
      }
    });
  }

  const HANDLERS = {
    loginuser: initLoginUser,
    admin_dashboard: initAdminDashboard,
    vetdashboard: initVetDashboard,
    volunteer_dashboard: initVolunteerDashboard,
    display_animals: initDisplayAnimals,
    display_users: initDisplayUsers,
    donationsite: initDonationSite,
    donation: initDonationSite,
    adoption: initAdoption,
    'cruelty management': initCrueltyManagement,
    viewallreports: initViewAllReports,
    displayalldonors: initDisplayAllDonors,
    display: initMedicalDisplay,
    animal_intakesite: initAnimalIntakeSite,
    register: initRegisterForm,
  };

  async function initPage() {
    await store().ready;
    const name = pageName();
    const handler = HANDLERS[name];
    if (handler) await handler();
  }

  global.SpcaPages = { initPage };
})(typeof window !== 'undefined' ? window : globalThis);
