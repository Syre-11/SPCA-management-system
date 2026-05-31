/**
 * Client-side mock database for static GitHub Pages hosting.
 * Persists mutations in localStorage; seeds from data/mockdb.json.
 */
(function (global) {
  const STORAGE_KEY = 'spca_mockdb_v1';

  function clone(obj) {
    return JSON.parse(JSON.stringify(obj));
  }

  function nextId(rows, field) {
    if (!rows.length) return 1;
    return Math.max(...rows.map((r) => Number(r[field]) || 0)) + 1;
  }

  class SpcaStore {
    constructor() {
      this.db = null;
      this.ready = this._init();
    }

    async _init() {
      const saved = localStorage.getItem(STORAGE_KEY);
      if (saved) {
        try {
          this.db = JSON.parse(saved);
          this._loadedFrom = 'localStorage';
          return;
        } catch (_) {
          /* fall through to seed */
        }
      }
      const base = global.SPCA_BASE_PATH || '';
      const paths = [
        `${base}data/mockdb.json`,
        'data/mockdb.json',
        '../data/mockdb.json',
        '../../data/mockdb.json',
      ];
      for (const url of paths) {
        try {
          const res = await fetch(url);
          if (res.ok) {
            this.db = await res.json();
            this._loadedFrom = url;
            this._persist();
            return;
          }
        } catch (_) {
          /* try next */
        }
      }
      if (global.SPCA_MOCK_SEED) {
        this.db = clone(global.SPCA_MOCK_SEED);
        this._loadedFrom = 'embedded seed';
        this._persist();
        return;
      }
      throw new Error(
        'Could not load mock data. Run npm run build and serve the dist/ folder.'
      );
    }

    _persist() {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(this.db));
    }

    reset() {
      localStorage.removeItem(STORAGE_KEY);
      return this._init();
    }

    table(name) {
      if (!this.db[name]) this.db[name] = [];
      return this.db[name];
    }

    findUser(username, position) {
      return this.table('systemuser').find(
        (u) => u.username === username && u.position === position
      );
    }

    verifyPassword(user, password) {
      if (!user) return false;
      return user.password === password;
    }

    addUser(user) {
      const rows = this.table('systemuser');
      if (rows.some((u) => u.username === user.username)) {
        throw new Error('User already exists');
      }
      user.SystemUser_ID = nextId(rows, 'SystemUser_ID');
      rows.push(user);
      this._persist();
      return user;
    }

    updateUser(id, patch) {
      const rows = this.table('systemuser');
      const idx = rows.findIndex((u) => String(u.SystemUser_ID) === String(id));
      if (idx === -1) throw new Error('User not found');
      rows[idx] = { ...rows[idx], ...patch };
      this._persist();
      return rows[idx];
    }

    deleteUser(id) {
      const rows = this.table('systemuser');
      const idx = rows.findIndex((u) => String(u.SystemUser_ID) === String(id));
      if (idx === -1) return false;
      rows.splice(idx, 1);
      this._persist();
      return true;
    }

    filterAnimals(params) {
      let rows = clone(this.table('animal'));
      const search = (params.search || '').trim().toLowerCase();
      if (search) {
        rows = rows.filter((a) =>
          (a.Animal_Name || '').toLowerCase().includes(search)
        );
      }
      Object.keys(params).forEach((key) => {
        if (key === 'search' || !params[key]) return;
        rows = rows.filter((a) => String(a[key]) === String(params[key]));
      });
      return rows.sort((a, b) =>
        (a.Animal_Name || '').localeCompare(b.Animal_Name || '')
      );
    }

    addAnimal(animal) {
      const rows = this.table('animal');
      animal.Animal_ID = nextId(rows, 'Animal_ID');
      rows.push(animal);
      if (animal.Kennel_ID) {
        const k = this.table('kennel').find((k) => k.Kennel_ID === animal.Kennel_ID);
        if (k) k.Availability = Math.max(0, (k.Availability || 1) - 1);
      }
      this._persist();
      return animal;
    }

    updateAnimal(id, patch) {
      const rows = this.table('animal');
      const idx = rows.findIndex((a) => String(a.Animal_ID) === String(id));
      if (idx === -1) throw new Error('Animal not found');
      rows[idx] = { ...rows[idx], ...patch };
      this._persist();
      return rows[idx];
    }

    deleteAnimal(id) {
      const rows = this.table('animal');
      const idx = rows.findIndex((a) => String(a.Animal_ID) === String(id));
      if (idx === -1) return false;
      const kennel = rows[idx].Kennel_ID;
      rows.splice(idx, 1);
      if (kennel) {
        const k = this.table('kennel').find((k) => k.Kennel_ID === kennel);
        if (k) k.Availability = (k.Availability || 0) + 1;
      }
      this._persist();
      return true;
    }

    addCrueltyReport(report, reporter) {
      const reporters = this.table('reporter');
      let reporterId = null;
      if (reporter && (reporter.FirstName || reporter.Surname || reporter.Email)) {
        reporterId = nextId(reporters, 'Reporter_ID');
        reporters.push({ Reporter_ID: reporterId, ...reporter });
      }
      const reports = this.table('crueltyreport');
      const id = nextId(reports, 'Report_ID');
      reports.push({
        Report_ID: id,
        ReportDate: report.ReportDate || new Date().toISOString().slice(0, 10),
        Description: report.Description,
        Location: report.Location,
        FK_Animal_ID: report.FK_Animal_ID ? Number(report.FK_Animal_ID) : null,
        FK_Reporter_ID: reporterId,
        FK_SystemUser_ID: report.FK_SystemUser_ID || 1,
        evidence: report.evidence || '',
        Status: 'New',
        Urgent: report.Urgent ? 1 : 0,
        deleted: 0,
      });
      this._persist();
      return id;
    }

    addAdoptionApplication(app) {
      const rows = this.table('adoptionapplication');
      const id = nextId(rows, 'Application_ID');
      rows.push({
        Application_ID: id,
        ...app,
        Application_Status: 'Pending',
        Application_Date: new Date().toISOString().slice(0, 10),
      });
      this._persist();
      return id;
    }

    findAdoptionApplication(input) {
      const rows = this.table('adoptionapplication');
      if (/^\d+$/.test(input)) {
        return rows.find((a) => String(a.Application_ID) === input);
      }
      return rows.find(
        (a) => a.Email && a.Email.toLowerCase() === input.toLowerCase()
      );
    }

    addDonation(donation) {
      const rows = this.table('alldonations');
      const id = nextId(rows, 'donor_id');
      rows.push({
        donor_id: id,
        DonationDate: new Date().toISOString().slice(0, 10),
        ...donation,
      });
      this._persist();
      return id;
    }

    addMedicalRecord(record) {
      const rows = this.table('medicalrecords');
      const id = nextId(rows, 'id');
      rows.push({
        id,
        Record_ID: id,
        Hide: 0,
        status: record.status || 'Pending',
        ...record,
      });
      this._persist();
      return id;
    }

    archiveMedicalRecord(id, hide) {
      const rows = this.table('medicalrecords');
      const row = rows.find((r) => String(r.id) === String(id));
      if (!row) return false;
      row.Hide = hide ? 1 : 0;
      this._persist();
      return true;
    }

    adminDashboardStats() {
      const animals = this.table('animal');
      const reports = this.table('crueltyreport').filter((r) => !r.deleted);
      const openReports = reports.filter((r) =>
        ['New', 'Open', 'In Progress'].includes(r.Status)
      );
      const pendingAdoptions = this.table('adoptionapplication').filter(
        (a) => a.Application_Status === 'Pending'
      );
      const now = new Date();
      const donations = this.table('alldonations').filter((d) => {
        const dt = new Date(d.DonationDate);
        return (
          dt.getMonth() === now.getMonth() &&
          dt.getFullYear() === now.getFullYear()
        );
      });
      const monthTotal = donations.reduce((s, d) => s + Number(d.Amount || 0), 0);

      const activities = [];
      reports.slice(-2).forEach((r) =>
        activities.push({
          type: 'Cruelty Report',
          ref: r.Report_ID,
          date: r.ReportDate,
        })
      );
      animals
        .slice()
        .sort((a, b) => (b.Animal_Arrival_Date || '').localeCompare(a.Animal_Arrival_Date || ''))
        .slice(0, 2)
        .forEach((a) =>
          activities.push({
            type: 'Animal Intake',
            ref: a.Animal_ID,
            date: a.Animal_Arrival_Date,
          })
        );
      this.table('alldonations')
        .slice(-2)
        .forEach((d) =>
          activities.push({ type: 'Donation', ref: d.donor_id, date: d.DonationDate })
        );
      this.table('adoptionapplication')
        .slice(-2)
        .forEach((a) =>
          activities.push({
            type: 'Adoption Application',
            ref: a.Application_ID,
            date: a.Application_Date,
          })
        );
      activities.sort((a, b) => (b.date || '').localeCompare(a.date || ''));

      return {
        totalAnimals: animals.length,
        openReports: openReports.length,
        pendingAdoptions: pendingAdoptions.length,
        donations: monthTotal,
        recentActivity: activities.slice(0, 5),
      };
    }

    joinAnimalName(id) {
      const a = this.table('animal').find(
        (x) => String(x.Animal_ID) === String(id)
      );
      return a ? a.Animal_Name : '';
    }

    distinctAnimalField(field) {
      const set = new Set();
      this.table('animal').forEach((a) => {
        if (a[field]) set.add(a[field]);
      });
      return [...set].sort();
    }

    getAnimal(id) {
      return this.table('animal').find(
        (a) => String(a.Animal_ID) === String(id)
      );
    }

    getAnimalByName(name) {
      return this.table('animal').find(
        (a) => a.Animal_Name && a.Animal_Name.toLowerCase() === name.toLowerCase()
      );
    }

    adoptableAnimals() {
      return this.table('animal').filter(
        (a) =>
          a.Animal_AdoptionStatus === 'Available' &&
          ['Excellent', 'Good'].includes(a.Animal_Health)
      );
    }

    getReport(id) {
      return this.table('crueltyreport').find(
        (r) => String(r.Report_ID) === String(id)
      );
    }

    getReporter(id) {
      if (id == null) return null;
      return this.table('reporter').find(
        (r) => String(r.Reporter_ID) === String(id)
      );
    }

    setCrueltyStatus(reportId, status) {
      const r = this.getReport(reportId);
      if (!r) return false;
      r.Status = status;
      this._persist();
      return true;
    }

    setAdoptionStatus(appId, status) {
      const rows = this.table('adoptionapplication');
      const row = rows.find((a) => String(a.Application_ID) === String(appId));
      if (!row) return false;
      row.Application_Status = status;
      this._persist();
      return true;
    }

    enrichAdoption(app) {
      const animal = this.getAnimalByName(app.Animal_Name);
      return {
        ...app,
        Animal_Breed: animal?.Animal_Breed || 'Unknown',
        Animal_Gender: animal?.Animal_Gender || 'Unknown',
      };
    }
  }

  global.SpcaStore = SpcaStore;
  global.spcaStore = new SpcaStore();
})(typeof window !== 'undefined' ? window : globalThis);
