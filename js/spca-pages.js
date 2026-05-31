/**
 * Fills static HTML pages with mock database content (replaces stripped PHP).
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

  function tbody(sel) {
    return document.querySelector(sel || 'table tbody');
  }

  function renderTableBody(tbodyEl, rows, rowHtml) {
    if (!tbodyEl) return;
    if (!rows.length) {
      tbodyEl.innerHTML =
        '<tr><td colspan="20" class="empty-state">No records found.</td></tr>';
      return;
    }
    tbodyEl.innerHTML = rows.map(rowHtml).join('');
  }

  function statusBadge(status) {
    const s = (status || 'Pending').toLowerCase();
    return `<span class="status-badge status-${s}">${esc(status || 'Pending')}</span>`;
  }

  function populateSelect(name, options, selected) {
    const sel = document.querySelector(`[name="${name}"]`);
    if (!sel || sel.tagName !== 'SELECT') return;
    const keep = sel.querySelector('option[value=""]')
      ? sel.querySelector('option[value=""]').outerHTML
      : '<option value="">All</option>';
    sel.innerHTML =
      keep +
      options.map((o) => `<option value="${esc(o)}">${esc(o)}</option>`).join('');
    if (selected) sel.value = selected;
  }

  function fillCards(counts) {
    const cards = document.querySelectorAll('.cards .card p');
    counts.forEach((v, i) => {
      if (cards[i] != null) cards[i].textContent = v;
    });
  }

  /* ---------- Adoption ---------- */
  function renderAdoptableAnimalCards() {
    document.querySelectorAll('section.MSPCA').forEach((section) => {
      const h = section.querySelector('h3');
      if (!h || !h.textContent.includes('Meet Our')) return;
      const row = section.querySelector('.row');
      if (!row) return;
      const animals = store().adoptableAnimals();
      const img = auth().abs('images/Logo.png');
      if (!animals.length) {
        row.innerHTML =
          '<div class="empty-state">No animals available for adoption right now.</div>';
        return;
      }
      row.innerHTML = animals
        .map(
          (a) => `
        <div class="row1">
          <h3>${esc(a.Animal_Name)}</h3>
          <p>${esc(a.Animal_Breed)} — ${esc(a.Animal_Species)}</p>
          <img src="${img}" alt="${esc(a.Animal_Name)}" style="max-width:180px;border-radius:8px;margin:10px auto;display:block"/>
          <a href="#adoption-form" class="apply-btn">Apply to Adopt</a>
        </div>`
        )
        .join('');
    });
    document.querySelector('.status-result')?.replaceChildren();
  }

  function getAdoptionRows() {
    const params = qs();
    let rows = store().table('adoptionapplication').map((a) => store().enrichAdoption(a));
    if (params.status && params.status !== 'all') {
      rows = rows.filter(
        (r) => (r.Application_Status || '').toLowerCase() === params.status.toLowerCase()
      );
    }
    if (params.show_deleted === 'deleted') {
      rows = rows.filter((r) => r.is_deleted);
    } else if (params.show_deleted !== 'deleted' && params.show_deleted !== 'all') {
      rows = rows.filter((r) => !r.is_deleted);
    }
    if (params.search) {
      const s = params.search.toLowerCase();
      rows = rows.filter(
        (r) =>
          `${r.First_Name} ${r.Last_Name}`.toLowerCase().includes(s) ||
          (r.Email || '').toLowerCase().includes(s) ||
          (r.Animal_Name || '').toLowerCase().includes(s)
      );
    }
    return rows;
  }

  function renderAdoptionApplicationsTable() {
    const rows = getAdoptionRows();
    const tb = tbody('.applications-table tbody') || tbody();
    renderTableBody(tb, rows, (rec) => {
      const st = (rec.Application_Status || 'Pending').toLowerCase();
      return `<tr>
        <td>${esc(rec.Application_ID)}</td>
        <td>${esc(rec.First_Name)} ${esc(rec.Last_Name)}</td>
        <td>${esc(rec.Email)}</td>
        <td>${esc(rec.Phone)}</td>
        <td>${esc(rec.Address)}</td>
        <td>${esc(rec.Animal_Name)}</td>
        <td>${esc(rec.Animal_Breed)}</td>
        <td>${esc(rec.Animal_Gender)}</td>
        <td>${statusBadge(rec.Application_Status)}</td>
        <td>${rec.is_deleted ? 'Yes' : 'No'}</td>
        <td>${fmtDate(rec.Application_Date)}</td>
        <td>
          <a href="adoption.management.html?id=${rec.Application_ID}" class="btn-update">Update</a>
        </td>
      </tr>`;
    });
  }

  async function initAdoption() {
    renderAdoptableAnimalCards();
    const animals = store().adoptableAnimals();
    const select = document.querySelector('select[name="animal"], #animal');
    if (select) {
      select.innerHTML =
        '<option value="">Select an animal</option>' +
        animals
          .map(
            (a) =>
              `<option value="${esc(a.Animal_Name)}">${esc(a.Animal_Name)} (${esc(a.Animal_Breed)})</option>`
          )
          .join('');
    }
    const form = document.querySelector('form.adoption-form, form');
    if (!form) return;
    form.addEventListener('submit', (e) => {
      const fd = new FormData(form);
      if (fd.get('check_status') != null) {
        e.preventDefault();
        const input = String(fd.get('input') || '').trim();
        const app = store().findAdoptionApplication(input);
        const out = document.querySelector('.status-result');
        if (out) {
          out.innerHTML = app
            ? `<p><strong>Application ID:</strong> ${app.Application_ID}</p>
               <p><strong>Status:</strong> ${statusBadge(app.Application_Status)}</p>
               <p><strong>Date:</strong> ${fmtDate(app.Application_Date)}</p>
               <p><strong>Animal:</strong> ${esc(app.Animal_Name)}</p>`
            : '<p>No application found.</p>';
        }
        return;
      }
      if (fd.get('submit_application') != null || form.querySelector('[name="first_name"]')) {
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
            is_deleted: 0,
          });
          alert(`Application submitted! Application ID: ${id}`);
          form.reset();
          renderAdoptableAnimalCards();
        } catch (err) {
          alert(err.message);
        }
      }
    });
  }

  async function initAdoptionTablePages() {
    auth().requireRole(['Administrative Staff', 'Volunteer Staff']);
    renderAdoptionApplicationsTable();
    const id = qs().id;
    if (id && pageName() === 'adoption.management') {
      const app = store()
        .table('adoptionapplication')
        .find((a) => String(a.Application_ID) === String(id));
      if (app) {
        document.querySelectorAll('input, textarea, select').forEach((el) => {
          const n = el.name;
          if (n === 'status') el.value = app.Application_Status;
        });
      }
    }
  }

  async function initAdopterProfile() {
    const id = qs().id || qs().app_id;
    const app = store()
      .table('adoptionapplication')
      .find((a) => String(a.Application_ID) === String(id));
    if (!app) return;
    fillText('[data-spca-field], .profile-section td', '');
    document.querySelectorAll('td').forEach((td) => {
      if (td.textContent.trim() === '' && td.previousElementSibling) {
        /* skip */
      }
    });
    const box = document.querySelector('.profile-section, .main-content, body');
    if (box && !document.getElementById('spca-profile-fill')) {
      const el = document.createElement('div');
      el.id = 'spca-profile-fill';
      el.className = 'spca-filters';
      el.innerHTML = `
        <h2>Adoption Application #${esc(app.Application_ID)}</h2>
        <p><strong>Applicant:</strong> ${esc(app.First_Name)} ${esc(app.Last_Name)}</p>
        <p><strong>Email:</strong> ${esc(app.Email)} | <strong>Phone:</strong> ${esc(app.Phone)}</p>
        <p><strong>Animal:</strong> ${esc(app.Animal_Name)} | ${statusBadge(app.Application_Status)}</p>
        <p><strong>Address:</strong> ${esc(app.Address)}</p>
        <p><strong>Experience:</strong> ${esc(app.Experience)}</p>`;
      const main = document.querySelector('h1');
      if (main) main.after(el);
    }
  }

  /* ---------- Cruelty ---------- */
  function renderCrueltyManageTable() {
    let reports = store().table('crueltyreport').filter((r) => !r.deleted);
    const tb = tbody('.applications-table tbody') || tbody();
    renderTableBody(tb, reports, (r) => {
      const animal = store().getAnimal(r.FK_Animal_ID);
      return `<tr>
        <td>${esc(r.Report_ID)}</td>
        <td>${esc(animal?.Animal_Name || '—')}</td>
        <td>${esc(r.Location)}</td>
        <td>${statusBadge(r.Status)}</td>
        <td>${r.Urgent ? 'Yes' : 'No'}</td>
        <td>
          <a href="ViewReport.html?id=${r.Report_ID}">View</a> |
          <a href="Follow-up.html?id=${r.Report_ID}">Follow-up</a>
        </td>
      </tr>`;
    });
  }

  async function initCrueltyManagement() {
    const animals = store().table('animal');
    const staff = store().table('systemuser');
    const animalSel = document.querySelector('[name="FK_Animal_ID"]');
    if (animalSel) {
      animalSel.innerHTML =
        '<option value="">Select animal (optional)</option>' +
        animals
          .map(
            (a) =>
              `<option value="${a.Animal_ID}">${a.Animal_ID} - ${esc(a.Animal_Name)}</option>`
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
    const form = document.querySelector('form');
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

  async function initCrueltyManage() {
    auth().requireRole(['Administrative Staff']);
    renderCrueltyManageTable();
    document.querySelectorAll('form[method="POST"]').forEach((form) => {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        const fd = new FormData(form);
        const reportId = fd.get('report_id');
        const action = fd.get('action');
        if (reportId && action) {
          store().setCrueltyStatus(reportId, action);
          location.reload();
        }
      });
    });
  }

  async function initViewReport() {
    auth().requireRole(['Administrative Staff']);
    const id = qs().id;
    const report = store().getReport(id);
    if (!report) {
      alert('Report not found in demo data.');
      return;
    }
    const animal = store().getAnimal(report.FK_Animal_ID);
    const reporter = store().getReporter(report.FK_Reporter_ID);
    const staff = store().table('systemuser').find(
      (u) => String(u.SystemUser_ID) === String(report.FK_SystemUser_ID)
    );
    const html = `
      <div class="spca-filters" id="spca-report-detail">
        <h2>Report #${esc(report.Report_ID)}</h2>
        <p><strong>Date:</strong> ${fmtDate(report.ReportDate)} | ${statusBadge(report.Status)} | Urgent: ${report.Urgent ? 'Yes' : 'No'}</p>
        <p><strong>Location:</strong> ${esc(report.Location)}</p>
        <p><strong>Description:</strong> ${esc(report.Description)}</p>
        <p><strong>Animal:</strong> ${esc(animal?.Animal_Name || 'Not linked')} (${esc(animal?.Animal_Breed || '')})</p>
        <p><strong>Reporter:</strong> ${reporter ? esc(reporter.FirstName + ' ' + reporter.Surname) : 'Anonymous'} — ${esc(reporter?.CellNumber || '')}</p>
        <p><strong>Assigned staff:</strong> ${staff ? esc(staff.firstname + ' ' + staff.surname) : '—'}</p>
      </div>`;
    const h1 = document.querySelector('h1');
    if (h1) h1.insertAdjacentHTML('afterend', html);
    const follow = document.querySelector('a.follow-up-btn, a[href*="follow-up"]');
    if (follow) follow.href = `Follow-up.html?id=${report.Report_ID}`;
  }

  async function initFollowUp() {
    auth().requireRole(['Administrative Staff']);
    const id = qs().id;
    const report = store().getReport(id);
    if (!report) return;
    const animal = store().getAnimal(report.FK_Animal_ID);
    const main = document.querySelector('.main-content, body');
    const box = document.createElement('div');
    box.className = 'spca-filters';
    box.innerHTML = `
      <h2>Follow-up — Report #${esc(report.Report_ID)}</h2>
      <p>${esc(animal?.Animal_Name || 'No animal')} — ${statusBadge(report.Status)}</p>
      <p>${esc(report.Description)}</p>`;
    const h1 = document.querySelector('h1');
    if (h1) h1.after(box);
    const form = document.querySelector('form');
    if (form) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        alert('Follow-up saved (demo — stored in browser session only).');
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
    if (params.show_deleted === 'deleted') reports = reports.filter((r) => r.deleted);
    else if (params.show_deleted !== 'all') reports = reports.filter((r) => !r.deleted);
    const tb = tbody('.reports-table tbody') || tbody();
    renderTableBody(tb, reports, (r) => {
      const animal = store().getAnimal(r.FK_Animal_ID);
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

  /* ---------- Volunteers ---------- */
  function renderVolunteerApplications() {
    let rows = store().table('VolunteerApplication').filter((v) => !v.IsHidden);
    const tb = tbody('.applications-table tbody') || tbody('table tbody');
    renderTableBody(tb, rows, (v) => `<tr>
      <td>${esc(v.Application_ID)}</td>
      <td>${esc(v.FirstName)} ${esc(v.LastName)}</td>
      <td>${esc(v.Email)}</td>
      <td>${esc(v.Phone)}</td>
      <td>${esc(v.Skills || '—')}</td>
      <td>${esc(v.Availability || '—')}</td>
      <td>${statusBadge(v.Status)}</td>
      <td>${fmtDate((v.CreatedAt || '').slice(0, 10))}</td>
    </tr>`);
  }

  async function initVolunteerPage() {
    renderVolunteerApplications();
    const form = document.querySelector('form[method="post"], form');
    if (form && form.querySelector('[name="FirstName"]')) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        const fd = new FormData(form);
        const rows = store().table('VolunteerApplication');
        const id = Math.max(...rows.map((r) => r.Application_ID || 0), 300) + 1;
        rows.push({
          Application_ID: id,
          FirstName: fd.get('FirstName'),
          LastName: fd.get('LastName'),
          Email: fd.get('Email'),
          Phone: fd.get('Phone'),
          Status: 'Pending',
          IsHidden: 0,
          CreatedAt: new Date().toISOString(),
          Skills: fd.get('Skills') || '',
          Availability: fd.get('Availability') || '',
        });
        store()._persist();
        alert(`Volunteer application submitted! ID: ${id}`);
        location.reload();
      });
    }
  }

  async function initVolunteerManagement() {
    auth().requireRole(['Volunteer Staff', 'Administrative Staff']);
    renderVolunteerApplications();
    const hours = store().table('VolunteerHours');
    const scheduleBodies = document.querySelectorAll('.schedule-table tbody');
    scheduleBodies.forEach((tb, idx) => {
      if (idx === 0) {
        renderTableBody(tb, hours, (h) => {
          const vol = store()
            .table('VolunteerApplication')
            .find((v) => String(v.Application_ID) === String(h.Volunteer_ID));
          return `<tr>
            <td>${esc(vol ? vol.FirstName + ' ' + vol.LastName : h.Volunteer_ID)}</td>
            <td>${esc(h.ActivityType)}</td>
            <td>${esc(h.Hours)}</td>
            <td>${fmtDate(h.DatePerformed)}</td>
            <td>${h.Verified ? 'Yes' : 'No'}</td>
          </tr>`;
        });
      }
    });
  }

  /* ---------- Animals / kennels ---------- */
  async function initDisplayAnimals() {
    const params = qs();
    const animals = store().filterAnimals(params);
    const tb = tbody();
    const img = auth().abs('images/Logo.png');
    const base = auth().abs('animal_intake_system/');
    renderTableBody(tb, animals, (a) => `<tr>
      <td><img class="animal-image" src="${img}" alt=""></td>
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
    </tr>`);
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
    populateSelect('Animal_Gender', store().distinctAnimalField('Animal_Gender'), params.Animal_Gender);
    populateSelect('Animal_Size', store().distinctAnimalField('Animal_Size'), params.Animal_Size);
    populateSelect('Animal_Health', store().distinctAnimalField('Animal_Health'), params.Animal_Health);
    populateSelect('Animal_AdoptionStatus', store().distinctAnimalField('Animal_AdoptionStatus'), params.Animal_AdoptionStatus);
    populateSelect('Kennel_ID', store().distinctAnimalField('Kennel_ID'), params.Kennel_ID);
  }

  function renderKennelTables() {
    const kennels = store().table('kennel');
    document.querySelectorAll('.kennel-table tbody, .kennel-edit-table tbody').forEach((tb) => {
      renderTableBody(tb, kennels, (k) => {
        const occupied = (k.Capacity || 1) - (k.Availability || 0);
        return `<tr>
          <td>${esc(k.Kennel_ID)}</td>
          <td>${esc(k.Location)}</td>
          <td>${esc(k.Capacity)}</td>
          <td>${esc(k.Availability)} available</td>
          <td>${occupied} in use</td>
        </tr>`;
      });
    });
  }

  async function initKennelPages() {
    renderKennelTables();
    const animals = store().table('animal');
    document.querySelectorAll('select[name*="Kennel"], select[name="Kennel_ID"]').forEach((sel) => {
      if (sel.options.length > 2) return;
      sel.innerHTML =
        '<option value="">Select kennel</option>' +
        kennelsOptions(animals);
    });
    function kennelsOptions() {
      return store()
        .table('kennel')
        .filter((k) => k.Availability > 0)
        .map((k) => `<option value="${esc(k.Kennel_ID)}">${esc(k.Kennel_ID)} (${esc(k.Location)})</option>`)
        .join('');
    }
  }

  async function initUpdateAnimal() {
    const id = qs().id;
    const animal = store().getAnimal(id);
    if (!animal) return;
    const form = document.querySelector('form');
    if (!form) return;
    Object.keys(animal).forEach((key) => {
      const el = form.querySelector(`[name="${key}"]`);
      if (el) el.value = animal[key] ?? '';
    });
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const fd = new FormData(form);
      const patch = {};
      fd.forEach((v, k) => {
        patch[k] = v;
      });
      store().updateAnimal(id, patch);
      alert('Animal updated.');
      location.href = auth().abs('animal_intake_system/display_animals.html');
    });
  }

  async function initAnimalIntakeSite() {
    const animals = store().table('animal');
    const next = animals.length ? Math.max(...animals.map((a) => a.Animal_ID)) + 1 : 1;
    fillText('[data-spca-next-id]', next);
    fillText('[data-spca-total-animals]', animals.length);
    document.querySelectorAll('h2, h3, p, .stat').forEach((el) => {
      if (el.textContent.includes('Total') && el.textContent.includes('Animal')) {
        el.textContent = el.textContent.replace(/\d+/, String(animals.length));
      }
    });
    populateSelect('Kennel_ID', store().table('kennel').filter((k) => k.Availability > 0).map((k) => k.Kennel_ID));
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

  /* ---------- Medical ---------- */
  async function initMedicalRecordsForm() {
    auth().requireRole(['Veterinary Staff']);
    const animals = store().table('animal');
    const vets = store().table('veterinarians');
    const animalSel = document.querySelector('select[name="Animal_ID"], select[name*="Animal"]');
    if (animalSel) {
      animalSel.innerHTML = animals
        .map(
          (a) =>
            `<option value="${a.Animal_ID}">${a.Animal_ID} - ${esc(a.Animal_Name)}</option>`
        )
        .join('');
    }
    const vetSel = document.querySelector('select[name="vet_id"]');
    if (vetSel) {
      vetSel.innerHTML = vets
        .map(
          (v) =>
            `<option value="${v.vet_id}">Dr. ${esc(v.vet_first_name)} ${esc(v.vet_last_name)}</option>`
        )
        .join('');
    }
    const form = document.querySelector('form');
    if (form) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        const fd = new FormData(form);
        store().addMedicalRecord({
          Animal_ID: Number(fd.get('Animal_ID')),
          procedure_type: fd.get('procedure_type'),
          vet_id: Number(fd.get('vet_id')),
          procedure_date: fd.get('procedure_date'),
          next_due_date: fd.get('next_due_date'),
          medication: fd.get('medication'),
          dosage: fd.get('dosage'),
          frequency: fd.get('frequency'),
          duration: fd.get('duration'),
          cost: Number(fd.get('cost') || 0),
          status: fd.get('status') || 'Pending',
          notes: fd.get('notes'),
        });
        alert('Medical record saved.');
        location.href = 'display.html';
      });
    }
  }

  async function initMedicalDisplay() {
    auth().requireRole(['Veterinary Staff', 'Administrative Staff']);
    const params = qs();
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
    let rows = store().table('medicalrecords').filter((r) => !r.Hide);
    if (params.search_animal_id) {
      rows = rows.filter((r) => String(r.Animal_ID) === String(params.search_animal_id));
    }
    const tb = tbody('.records-table tbody') || tbody();
    renderTableBody(tb, rows, (r) => {
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
    const archTb = document.querySelectorAll('.records-table tbody')[1];
    if (archTb) {
      const archived = store().table('medicalrecords').filter((r) => r.Hide);
      renderTableBody(archTb, archived, (r) => `<tr>
        <td>${esc(r.id)}</td>
        <td>${esc(r.Animal_ID)}</td>
        <td>${esc(r.procedure_type)}</td>
        <td><a href="?recover_id=${r.id}">Recover</a></td>
      </tr>`);
    }
  }

  async function initVetDashboard() {
    auth().requireRole(['Veterinary Staff']);
    const animals = store().table('animal');
    const records = store().table('medicalrecords');
    const vets = store().table('veterinarians').filter((v) => v.is_active);
    const pending = records.filter((r) => r.status === 'Pending' && !r.Hide);
    fillCards([animals.length, vets.length, pending.length]);

    const alertsBox = document.querySelector('.sticky-note');
    document.querySelectorAll('.sticky-note').forEach((note) => {
      if (!note.textContent.includes('Alerts')) return;
      const pendingList = records
        .filter((r) => r.status === 'Pending' && !r.Hide)
        .slice(0, 5);
      if (!pendingList.length) {
        note.querySelector('.alert-item')?.remove();
        const p = note.querySelector('p');
        if (p) p.textContent = 'No pending alerts at this time.';
        return;
      }
      const container = note;
      container.querySelectorAll('.alert-item, p').forEach((el) => el.remove());
      pendingList.forEach((m) => {
        const a = store().getAnimal(m.Animal_ID);
        const div = document.createElement('div');
        div.className = 'alert-item';
        div.innerHTML = `<strong>💉 ${esc(a?.Animal_Name || m.Animal_ID)}</strong> (${esc(a?.Animal_Species || '')})<br>
          Status: ${esc(m.status)}<br>Due: ${fmtDate(m.next_due_date)}<br>
          <strong>Notes:</strong> ${esc(m.notes)}`;
        container.appendChild(div);
      });
    });

    document.querySelectorAll('.sticky-note').forEach((note) => {
      if (!note.textContent.includes('Veterinarians')) return;
      note.querySelectorAll('.vet-card').forEach((c) => c.remove());
      vets.forEach((v) => {
        const div = document.createElement('div');
        div.className = 'vet-card';
        div.innerHTML = `<h3>Dr. ${esc(v.vet_first_name)} ${esc(v.vet_last_name)}</h3>
          <p>📧 ${esc(v.email || '')}</p>
          <p>📱 ${esc(v.phone || '')}</p>
          <p>🩺 ${esc(v.specialization)}</p>`;
        note.appendChild(div);
      });
    });

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
              backgroundColor: ['#4CAF50', '#2196F3', '#FF9800', '#9C27B0', '#795548'],
            },
          ],
        },
      });
    }
  }

  /* ---------- Users / admin ---------- */
  async function initAdminDashboard() {
    auth().requireRole(['Administrative Staff']);
    const stats = store().adminDashboardStats();
    fillCards([
      stats.totalAnimals,
      stats.openReports,
      stats.pendingAdoptions,
      'R ' + Number(stats.donations).toFixed(2),
    ]);
    const ul = document.querySelector('.recent-activity ul');
    if (ul) {
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

  async function initDisplayUsers() {
    auth().requireRole(['Administrative Staff']);
    const search = (qs().search || '').trim().toLowerCase();
    let users = store().table('systemuser');
    if (search) {
      users = users.filter((u) =>
        (u.username || '').toLowerCase().includes(search)
      );
    }
    const tb = tbody();
    renderTableBody(tb, users, (u) => {
      const base = auth().abs('registerUser/');
      return `<tr>
        <td>${esc(u.SystemUser_ID)}</td>
        <td>${esc(u.username)}</td>
        <td>${esc(u.firstname)}</td>
        <td>${esc(u.surname)}</td>
        <td>${esc(u.dateOfBirth)}</td>
        <td>${esc(u.email)}</td>
        <td>••••••</td>
        <td>${esc(u.phone)}</td>
        <td>${esc(u.gender)}</td>
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

  async function initEditUser() {
    const id = qs().id;
    const user = store().table('systemuser').find((u) => String(u.SystemUser_ID) === String(id));
    if (!user) return;
    const form = document.querySelector('form');
    if (!form) return;
    ['username', 'firstname', 'surname', 'dateOfBirth', 'email', 'phone', 'gender', 'position'].forEach((f) => {
      const el = form.querySelector(`[name="${f}"]`);
      if (el && user[f] != null) el.value = user[f];
    });
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      alert('User updated (demo).');
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
    const statEls = document.querySelectorAll('.stats-number, .total-amount, .impact-number');
    statEls.forEach((el, i) => {
      if (i === 0) el.textContent = 'R ' + Number(total).toFixed(2);
      if (i === 1) el.textContent = donors;
    });
    const form = document.querySelector('#donation-form-container form, form.donation-form');
    if (form) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        const fd = new FormData(form);
        store().addDonation({
          name: fd.get('name') || fd.get('first_name') || 'Guest',
          surname: fd.get('surname') || fd.get('last_name') || '',
          email: fd.get('email') || '',
          CellNumber: fd.get('phone') || '',
          Amount: Number(fd.get('amount') || fd.get('Amount') || 0),
          PaymentMethod: fd.get('payment_method') || 'Card',
        });
        alert('Thank you for your donation (demo).');
        location.reload();
      });
    }
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
    rows.sort((a, b) => (b.DonationDate || '').localeCompare(a.DonationDate || ''));
    renderTableBody(tbody('.donations-table tbody') || tbody(), rows, (d) => `<tr>
      <td>${esc(d.donor_id)}</td>
      <td>${esc(d.name)} ${esc(d.surname)}</td>
      <td>${esc(d.email)}</td>
      <td>R ${Number(d.Amount).toFixed(2)}</td>
      <td>${fmtDate(d.DonationDate)}</td>
      <td>${esc(d.PaymentMethod)}</td>
    </tr>`);
  }

  async function initVolunteerDashboard() {
    auth().requireRole(['Volunteer Staff']);
    const apps = store().table('VolunteerApplication');
    const hours = store().table('VolunteerHours');
    const now = new Date();
    const newApps = apps.filter((a) => a.Status === 'Pending').length;
    const approved = apps.filter((a) => a.Status === 'Approved' && !a.IsHidden).length;
    const monthHours = hours
      .filter((h) => {
        if (!h.Verified) return false;
        const d = new Date(h.DatePerformed);
        return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
      })
      .reduce((s, h) => s + Number(h.Hours || 0), 0);
    fillCards([newApps, approved, monthHours]);
    const list = document.querySelector('.recent-activity ul, .notifications ul');
    if (list) {
      const pending = apps.filter((a) => a.Status === 'Pending').slice(0, 5);
      list.innerHTML = pending.length
        ? pending.map((a) => `<li>${esc(a.FirstName)} ${esc(a.LastName)} — ${esc(a.Status)}</li>`).join('')
        : '<li>No pending applications.</li>';
    }
  }

  async function initLoginUser() {
    const err = document.getElementById('spca-login-error');
    const hint = document.createElement('div');
    hint.className = 'spca-demo-hint';
    hint.innerHTML =
      '<strong>Demo logins</strong> (pre-filled):<br>' +
      'Admin — <code>admin</code> / <code>Admin123!</code><br>' +
      'Vet — <code>drsmith</code> / <code>Vet123!</code><br>' +
      'Volunteer — <code>volunteer1</code> / <code>Vol123!</code>';
    const form = document.querySelector('.login-container form');
    if (form && !document.querySelector('.spca-demo-hint')) {
      form.parentElement.appendChild(hint);
      form.querySelector('[name="username"]').value = 'admin';
      form.querySelector('[name="password"]').value = 'Admin123!';
      form.querySelector('[name="position"]').value = 'Administrative Staff';
    }
    if (err && !err.textContent) err.textContent = '';
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

  function showDataLoadedBanner() {
    if (document.getElementById('spca-data-ok')) return;
    const n = store().table('animal').length;
    const el = document.createElement('div');
    el.id = 'spca-data-ok';
    el.style.cssText =
      'background:#d1fae5;color:#065f46;padding:6px 12px;text-align:center;font-size:13px;';
    el.textContent = `Demo data loaded: ${n} animals, ${store().table('adoptionapplication').length} adoption apps, ${store().table('crueltyreport').length} cruelty reports. Changes save in this browser.`;
    const banner = document.getElementById('spca-demo-banner');
    if (banner) banner.after(el);
    else document.body.prepend(el);
  }

  const HANDLERS = {
    loginuser: initLoginUser,
    register: initRegisterForm,
    admin_dashboard: initAdminDashboard,
    vetdashboard: initVetDashboard,
    volunteer_dashboard: initVolunteerDashboard,
    display_animals: initDisplayAnimals,
    display_users: initDisplayUsers,
    edit_user: initEditUser,
    update_user: initEditUser,
    donationsite: initDonationSite,
    donation: initDonationSite,
    displayalldonors: initDisplayAllDonors,
    adoption: initAdoption,
    adoption_records: initAdoptionTablePages,
    'adoption.management': initAdoptionTablePages,
    adopter_profile: initAdopterProfile,
    'cruelty management': initCrueltyManagement,
    'cruelty manage': initCrueltyManage,
    viewallreports: initViewAllReports,
    viewreport: initViewReport,
    'follow-up': initFollowUp,
    volunteer: initVolunteerPage,
    volunteer_records: initVolunteerPage,
    volunteer_management: initVolunteerManagement,
    medicalrecords: initMedicalRecordsForm,
    display: initMedicalDisplay,
    animal_intakesite: initAnimalIntakeSite,
    edit_kennels: initKennelPages,
    allocate_kennel: initKennelPages,
    update_animal: initUpdateAnimal,
  };

  async function initPage() {
    await store().ready;
    showDataLoadedBanner();
    const name = pageName();
    const handler = HANDLERS[name];
    if (handler) {
      try {
        await handler();
      } catch (err) {
        console.error('SPCA page init error:', name, err);
      }
    } else if (document.querySelector('table tbody')) {
      console.warn('No mock handler for page:', name);
    }
  }

  global.SpcaPages = { initPage, HANDLERS };
})(typeof window !== 'undefined' ? window : globalThis);
