document.addEventListener('DOMContentLoaded', () => {

  // ============================
  // 1) OBJECTIFS (Create Recipe)
  // ============================
  const objectivesGrid = document.getElementById('objectivesGrid');

  if (objectivesGrid) {
    objectivesGrid.querySelectorAll('.objective-card').forEach(card => {
      const checkbox = card.querySelector('input[type="checkbox"]');

      // si déjà coché
      if (checkbox && checkbox.checked) card.classList.add('active');

      card.addEventListener('click', (e) => {
        e.preventDefault();
        if (!checkbox) return;

        checkbox.checked = !checkbox.checked;
        card.classList.toggle('active', checkbox.checked);
      });
    });
  }

  // ============================
  // 2) MODAL (Click Recipe)
  // ============================
  const backdrop = document.getElementById('recipeModalBackdrop');
  const modal = document.getElementById('recipeModal');
  const closeBtn = document.getElementById('closeModalBtn');
  const toggleEditBtn = document.getElementById('toggleEditBtn');

  const mTitle = document.getElementById('mTitle');
  const mDescSmall = document.getElementById('mDescSmall');
  const mImg = document.getElementById('mImg');
  const mKcalChip = document.getElementById('mKcalChip');
  const mProtChip = document.getElementById('mProtChip');
  const mTypeChip = document.getElementById('mTypeChip');
  const mIngredientsList = document.getElementById('mIngredientsList');
  const mPreparationList = document.getElementById('mPreparationList');

  const updateForm = document.getElementById('updateForm');
  const deleteForm = document.getElementById('deleteForm');
  const updToken = document.getElementById('updToken');
  const delToken = document.getElementById('delToken');

  const fTitle = document.getElementById('fTitle');
  const fDescription = document.getElementById('fDescription');
  const fKcal = document.getElementById('fKcal');
  const fProteins = document.getElementById('fProteins');
  const fTypeMeal = document.getElementById('fTypeMeal');
  const fIngredients = document.getElementById('fIngredients');
  const fPreparation = document.getElementById('fPreparation');

  let editMode = false;

  function splitToList(text) {
    const lines = (text || '').split(/\r?\n/).map(l => l.trim()).filter(Boolean);
    return lines.length ? lines : ['—'];
  }

  function setEditMode(on) {
    editMode = on;
    if (modal) modal.classList.toggle('edit-on', on);
  }

  function closeModal() {
    if (!backdrop) return;
    backdrop.style.display = 'none';
    backdrop.setAttribute('aria-hidden', 'true');
  }

  function openModalFromCard(card) {
    if (!backdrop || !modal) return;
    const data = card.dataset;

    // Remplir UI
    if (mTitle) mTitle.textContent = data.title || 'Recipe';
    if (mDescSmall) mDescSmall.textContent = data.description || '';
    if (mImg) mImg.src = data.image || '';

    if (mKcalChip) mKcalChip.textContent = '🔥 ' + (data.kcal || '—') + ' kcal';
    if (mProtChip) mProtChip.textContent = '💪 ' + (data.proteins || '—') + ' g protéines';
    if (mTypeChip) mTypeChip.textContent = '🍽️ ' + (data.type || '—');

    if (mIngredientsList) {
      mIngredientsList.innerHTML = '';
      splitToList(data.ingredients).forEach(x => {
        const li = document.createElement('li');
        li.textContent = x;
        mIngredientsList.appendChild(li);
      });
    }

    if (mPreparationList) {
      mPreparationList.innerHTML = '';
      splitToList(data.preparation).forEach(x => {
        const li = document.createElement('li');
        li.textContent = x;
        mPreparationList.appendChild(li);
      });
    }

    // Forms
    if (updateForm && data.updateUrl) updateForm.action = data.updateUrl;
    if (deleteForm && data.deleteUrl) deleteForm.action = data.deleteUrl;
    if (updToken) updToken.value = data.updateToken || '';
    if (delToken) delToken.value = data.deleteToken || '';

    // Prefill edit fields
    if (fTitle) fTitle.value = data.title || '';
    if (fDescription) fDescription.value = data.description || '';
    if (fKcal) fKcal.value = data.kcal || '';
    if (fProteins) fProteins.value = data.proteins || '';
    if (fTypeMeal) fTypeMeal.value = data.type || 'BREAKFAST';
    if (fIngredients) fIngredients.value = data.ingredients || '';
    if (fPreparation) fPreparation.value = data.preparation || '';

    setEditMode(false);

    backdrop.style.display = 'flex';
    backdrop.setAttribute('aria-hidden', 'false');
  }

  // Bind cards click
  document.querySelectorAll('.js-open-modal').forEach(card => {
    card.addEventListener('click', () => openModalFromCard(card));
  });

  if (closeBtn) closeBtn.addEventListener('click', closeModal);

  if (backdrop) {
    backdrop.addEventListener('click', (e) => {
      if (e.target === backdrop) closeModal();
    });
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
  });

  if (toggleEditBtn) {
    toggleEditBtn.addEventListener('click', () => setEditMode(!editMode));
  }

  // ============================
  // 3) SLIDERS (Filters)
  // ============================
  const kcalRange = document.getElementById('kcalRange');
  const protRange = document.getElementById('protRange');
  const kcalVal   = document.getElementById('kcalVal');
  const protVal   = document.getElementById('protVal');

  const kcalHidden = document.getElementById('kcalHidden');
  const protHidden = document.getElementById('protHidden');

  function applyKcal(val) {
    if (!kcalVal || !kcalHidden) return;
    kcalVal.textContent = String(val);
    kcalHidden.value = String(val);
  }

  function applyProt(val) {
    if (!protVal || !protHidden) return;
    protVal.textContent = String(val);
    protHidden.value = String(val);
  }

  if (kcalRange) kcalRange.addEventListener('input', () => applyKcal(kcalRange.value));
  if (protRange) protRange.addEventListener('input', () => applyProt(protRange.value));

  document.querySelectorAll('.slider-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      const targetId = btn.dataset.target;
      const step = parseInt(btn.dataset.step || '0', 10);

      const input = document.getElementById(targetId);
      if (!input) return;

      const min = parseInt(input.min || '0', 10);
      const max = parseInt(input.max || '999999', 10);

      let val = parseInt(input.value || '0', 10) + step;
      val = Math.max(min, Math.min(max, val));
      input.value = String(val);

      if (targetId === 'kcalRange') applyKcal(val);
      if (targetId === 'protRange') applyProt(val);
    });
  });

});
