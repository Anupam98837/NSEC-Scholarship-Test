{{-- resources/views/student/personalAcademicDetails.blade.php --}}

@section('title','Personal & Academic Details')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
<style>
  /* minimal CSS */
  .pad-wrap{ max-width: 900px; margin: 18px auto 44px; padding: 0 12px; }
  .pad-card{ border-radius: 16px; border: 1px solid rgba(0,0,0,.08); }
  .pad-card .form-control,.pad-card .form-select{ border-radius: 12px; }
  .pad-muted{ color:#6c757d; }
</style>
@endpush

@section('content')
<div class="pad-wrap">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h3 class="mb-0">Personal & Academic Details</h3>
      <div class="pad-muted">All fields are optional. Fill what you have and press Save.</div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Back</a>
    </div>
  </div>

  <div class="card pad-card shadow-sm">
    <div class="card-body p-4">
      <div id="alertBox" class="alert d-none" role="alert"></div>

      <form id="padForm" class="row g-3" autocomplete="off">
        {{-- Guardian --}}
        <div class="col-md-6">
          <label class="form-label">Guardian Name</label>
          <input type="text" class="form-control" id="guardian_name" name="guardian_name" placeholder="e.g. Mr. S. Das">
        </div>

        <div class="col-md-6">
          <label class="form-label">Guardian Number</label>
          <input type="text" class="form-control" id="guardian_number" name="guardian_number" placeholder="e.g. 98XXXXXXXX">
          <div class="form-text">Keep digits only if possible.</div>
        </div>

        {{-- Class --}}
        <div class="col-md-6">
          <label class="form-label">Class</label>
          <select class="form-select" id="class" name="class">
            <option value="">— Select (optional) —</option>
            <option value="X">Class X</option>
            <option value="XI">Class XI</option>
            <option value="XII">Class XII</option>
            <option value="Passed out">Passed out</option>
          </select>
        </div>

        {{-- Year of passout (Dropdown) --}}
        <div class="col-md-6">
          <label class="form-label">Year of Passout</label>
          <select class="form-select" id="year_of_passout" name="year_of_passout">
            <option value="">— Select Year (optional) —</option>
            @php
              $cur = (int) date('Y');
              $start = $cur - 15;
              $end = $cur + 10;
            @endphp
            @for($y = $end; $y >= $start; $y--)
              <option value="{{ $y }}">{{ $y }}</option>
            @endfor
          </select>
          <div class="form-text">Year only.</div>
        </div>

        {{-- Board --}}
        <div class="col-md-6">
          <label class="form-label">Board</label>
          <select class="form-select" id="board" name="board">
            <option value="">— Select (optional) —</option>
            <option value="CBSC">CBSC</option>
            <option value="ISC">ISC</option>
            <option value="WBHSC">WBHSC</option>
            <option value="Bihar">Bihar</option>
            <option value="Jharkhand">Jharkhand</option>
            <option value="Open University">Open University</option>
            <option value="Others">Others</option>
          </select>
        </div>

        {{-- Exam type --}}
        <div class="col-md-6">
          <label class="form-label">Exam Type</label>
          <select class="form-select" id="exam_type" name="exam_type">
            <option value="">— Select (optional) —</option>
            <option value="JEE">JEE</option>
            <option value="NEET">NEET</option>
            {{-- custom options will be inserted by JS --}}
            <option value="Other">Other</option>
          </select>
          <div class="form-text">If your exam is not listed, select “Other”.</div>
        </div>

        <div class="col-12 d-none" id="examTypeOtherWrap">
          <label class="form-label">Other Exam Type</label>
          <input type="text" class="form-control" id="exam_type_other" placeholder="e.g. CUET">
          <div class="form-text">This value will be saved and appear in the dropdown next time.</div>
        </div>

        <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2">
          <div class="pad-muted small" id="statusText">Ready.</div>
          <div class="d-flex gap-2">
            <button type="button" id="btnReset" class="btn btn-outline-secondary">Reset</button>
            <button type="submit" id="btnSave" class="btn btn-primary">Save Details</button>
          </div>
        </div>
      </form>

    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const API_SHOW   = '/api/student/personal-academic-details';
  const API_UPSERT = '/api/student/personal-academic-details';

  const alertBox   = document.getElementById('alertBox');
  const statusText = document.getElementById('statusText');
  const btnSave    = document.getElementById('btnSave');
  const btnReset   = document.getElementById('btnReset');
  const form       = document.getElementById('padForm');

  const examTypeSel = document.getElementById('exam_type');
  const otherWrap   = document.getElementById('examTypeOtherWrap');
  const otherInput  = document.getElementById('exam_type_other');

  const BASE_EXAMS = new Set(['', 'JEE', 'NEET', 'Other']); // base options that are always present

  function getToken(){
    return localStorage.getItem('token') || sessionStorage.getItem('token') || '';
  }

  function headersJSON(){
    const h = { 'Accept':'application/json', 'Content-Type':'application/json' };
    const t = getToken();
    if (t) h['Authorization'] = 'Bearer ' + t;
    return h;
  }

  function showAlert(type, msg){
    alertBox.className = 'alert alert-' + type;
    alertBox.textContent = msg;
    alertBox.classList.remove('d-none');
  }

  function hideAlert(){
    alertBox.classList.add('d-none');
    alertBox.textContent = '';
  }

  function setLoading(loading, text){
    btnSave.disabled = loading;
    btnReset.disabled = loading;
    statusText.textContent = text || (loading ? 'Working...' : 'Ready.');
  }

  function hasOption(selectEl, value){
    value = String(value ?? '');
    return Array.from(selectEl.options).some(o => String(o.value) === value);
  }

  function ensureExamOption(value){
    value = (value ?? '').toString().trim();
    if (!value) return;

    // If it's a base option, do nothing
    if (BASE_EXAMS.has(value)) return;

    // If already exists, do nothing
    if (hasOption(examTypeSel, value)) return;

    // Insert custom option just before "Other"
    const opt = document.createElement('option');
    opt.value = value;
    opt.textContent = value;

    const otherOpt = Array.from(examTypeSel.options).find(o => o.value === 'Other');
    if (otherOpt && otherOpt.parentNode) {
      otherOpt.parentNode.insertBefore(opt, otherOpt);
    } else {
      examTypeSel.appendChild(opt);
    }
  }

  function setForm(data){
    document.getElementById('guardian_name').value   = data?.guardian_name ?? '';
    document.getElementById('guardian_number').value = data?.guardian_number ?? '';
    document.getElementById('class').value           = data?.class ?? '';
    document.getElementById('board').value           = data?.board ?? '';
    document.getElementById('year_of_passout').value = data?.year_of_passout ?? '';

    const savedExam = (data?.exam_type ?? '').toString().trim();

    // If saved exam is custom (CUET etc), add it into dropdown
    if (savedExam && !BASE_EXAMS.has(savedExam)) {
      ensureExamOption(savedExam);
      examTypeSel.value = savedExam;
      otherWrap.classList.add('d-none');
      otherInput.value = '';
      return;
    }

    // Otherwise set normal value
    examTypeSel.value = savedExam || '';
    if (examTypeSel.value === 'Other'){
      otherWrap.classList.remove('d-none');
    }else{
      otherWrap.classList.add('d-none');
      otherInput.value = '';
    }
  }

  function getPayload(){
    let examType = examTypeSel.value || null;

    // If user picked Other, use typed value as the saved exam_type
    if (examType === 'Other'){
      const typed = (otherInput.value || '').trim();
      examType = typed ? typed : 'Other';
    }

    const yopRaw = document.getElementById('year_of_passout').value;
    const yop = yopRaw ? parseInt(yopRaw, 10) : null;

    return {
      guardian_name:   (document.getElementById('guardian_name').value || '').trim() || null,
      guardian_number: (document.getElementById('guardian_number').value || '').trim() || null,
      class:           document.getElementById('class').value || null,
      board:           document.getElementById('board').value || null,
      exam_type:       examType,
      year_of_passout: Number.isFinite(yop) ? yop : null,
    };
  }

  async function loadExisting(){
    hideAlert();
    setLoading(true, 'Loading your details...');
    try{
      const res = await fetch(API_SHOW, { method:'GET', headers: headersJSON() });
      const json = await res.json().catch(() => ({}));

      if (!res.ok || json?.success === false){
        throw new Error(json?.message || 'Failed to load details');
      }

      setForm(json?.data || {});
      setLoading(false, 'Loaded.');
    }catch(e){
      setLoading(false, 'Ready.');
      showAlert('warning', e.message || 'Could not load details.');
    }
  }

  examTypeSel.addEventListener('change', () => {
    if (examTypeSel.value === 'Other'){
      otherWrap.classList.remove('d-none');
      otherInput.focus();
    }else{
      otherWrap.classList.add('d-none');
      otherInput.value = '';
    }
  });

  btnReset.addEventListener('click', () => {
    hideAlert();
    form.reset();
    otherWrap.classList.add('d-none');
    otherInput.value = '';
    statusText.textContent = 'Reset done.';
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideAlert();
    setLoading(true, 'Saving...');

    try{
      const payload = getPayload();

      const res = await fetch(API_UPSERT, {
        method:'POST',
        headers: headersJSON(),
        body: JSON.stringify(payload)
      });

      const json = await res.json().catch(() => ({}));

      if (!res.ok || json?.success === false){
        const errs = json?.errors || {};
        const firstKey = Object.keys(errs)[0];
        const firstMsg = firstKey ? (errs[firstKey][0] || 'Validation failed') : (json?.message || 'Save failed');
        throw new Error(firstMsg);
      }

      // ✅ If saved exam_type is custom, add to dropdown and select it
      const saved = (json?.data?.exam_type ?? payload.exam_type ?? '').toString().trim();
      if (saved && !BASE_EXAMS.has(saved)) {
        ensureExamOption(saved);
        examTypeSel.value = saved;
        otherWrap.classList.add('d-none');
        otherInput.value = '';
      } else {
        setForm(json?.data || {});
      }

      showAlert('success', 'Saved successfully!');
      setLoading(false, 'Saved.');
    }catch(e){
      showAlert('danger', e.message || 'Save failed.');
      setLoading(false, 'Ready.');
    }
  });

  loadExisting();
</script>
@endpush