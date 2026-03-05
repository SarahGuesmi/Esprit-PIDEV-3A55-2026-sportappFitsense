document.addEventListener('DOMContentLoaded', () => {

  // ============================
  // 0) ENSURE ALL MODALS ARE CLOSED ON PAGE LOAD
  // ============================
  const recipeModalBackdrop = document.getElementById('recipeModalBackdrop');
  const confirmDeleteBackdrop = document.getElementById('confirmDeleteBackdrop');
  
  if (recipeModalBackdrop) {
    recipeModalBackdrop.style.display = 'none';
    recipeModalBackdrop.setAttribute('aria-hidden', 'true');
  }
  
  if (confirmDeleteBackdrop) {
    confirmDeleteBackdrop.style.display = 'none';
    confirmDeleteBackdrop.setAttribute('aria-hidden', 'true');
  }

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
  const mObjectifsContainer = document.getElementById('mObjectifsContainer');
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

  const saveBtn = document.getElementById('saveBtn');

  function setEditMode(on) {
    editMode = on;

    // classe globale (si tu veux garder)
    if (modal) modal.classList.toggle('edit-on', on);

    // ✅ afficher/cacher les champs
    document.querySelectorAll('.edit-field').forEach(el => {
      el.classList.toggle('hidden', !on);
    });

    document.querySelectorAll('.view-field').forEach(el => {
      el.classList.toggle('hidden', on);
    });

    // ✅ afficher/cacher le bouton Save
    if (saveBtn) saveBtn.classList.toggle('hidden', !on);

    // optionnel: changer texte du bouton edit
    if (toggleEditBtn) {
      toggleEditBtn.innerHTML = on
        ? '<i class="fas fa-times mr-2"></i> Cancel'
        : '<i class="fas fa-edit mr-2"></i> Edit';
    }
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

    // Objectives
    const cardObjectifs = JSON.parse(data.objectifs || '[]');
    if (mObjectifsContainer) {
      mObjectifsContainer.innerHTML = '';
      cardObjectifs.forEach(obj => {
        const span = document.createElement('span');
        span.className = 'text-[10px] px-2 py-1 bg-blue-500/10 text-blue-400 rounded-lg border border-blue-500/20 uppercase font-bold tracking-wider';
        span.textContent = obj.replace('_', ' ');
        mObjectifsContainer.appendChild(span);
      });
    }

    // Prefill Objectives Checkboxes
    document.querySelectorAll('.obj-checkbox').forEach(cb => {
      cb.checked = cardObjectifs.includes(cb.value);
    });

    // Forms
    if (updateForm && data.updateUrl) updateForm.action = data.updateUrl;
    if (deleteForm && data.deleteUrl) deleteForm.action = data.deleteUrl;
    if (updToken) updToken.value = data.updateToken || '';
    console.log("ACTION:", updateForm?.action);
    console.log("TOKEN:", updToken?.value);
    if (delToken) delToken.value = data.deleteToken || '';
    console.log("DELETE ACTION:", deleteForm?.action);
    console.log("DELETE TOKEN:", delToken?.value);

    // Prefill edit fields
    if (fTitle) fTitle.value = data.title || '';
    if (fDescription) fDescription.value = data.description || '';
    if (fKcal) fKcal.value = data.kcal || '';
    if (fProteins) fProteins.value = data.proteins || '';
    if (fTypeMeal) fTypeMeal.value = (data.type || 'BREAKFAST').toUpperCase();
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

  // Use event delegation for delete button (with once flag to prevent multiple submissions)
  let isDeleting = false;
  
  document.addEventListener('click', function(e) {
    // Check if the clicked element is the delete button
    if (e.target && (e.target.id === 'deleteBtn' || e.target.closest('#deleteBtn'))) {
      e.preventDefault();
      e.stopPropagation();
      console.log("Delete button clicked via delegation!");
      
      const confirmDeleteBackdrop = document.getElementById('confirmDeleteBackdrop');
      const confirmRecipeTitle = document.getElementById('confirmRecipeTitle');
      
      if (confirmDeleteBackdrop && confirmRecipeTitle && mTitle) {
        console.log("Showing confirmation modal");
        confirmRecipeTitle.textContent = mTitle.textContent || 'this recipe';
        confirmDeleteBackdrop.style.display = 'flex';
        confirmDeleteBackdrop.setAttribute('aria-hidden', 'false');
      } else {
        console.error("Missing elements for delete confirmation");
      }
    }
    
    // Cancel delete
    if (e.target && (e.target.id === 'cancelDeleteBtn' || e.target.closest('#cancelDeleteBtn'))) {
      e.preventDefault();
      console.log("Cancel delete clicked");
      const confirmDeleteBackdrop = document.getElementById('confirmDeleteBackdrop');
      if (confirmDeleteBackdrop) {
        confirmDeleteBackdrop.style.display = 'none';
        confirmDeleteBackdrop.setAttribute('aria-hidden', 'true');
      }
    }
    
    // Confirm delete
    if (e.target && (e.target.id === 'confirmDeleteBtn' || e.target.closest('#confirmDeleteBtn'))) {
      e.preventDefault();
      
      if (isDeleting) {
        console.log("Already deleting, ignoring click");
        return;
      }
      
      isDeleting = true;
      console.log("Confirm delete clicked");
      const confirmDeleteBackdrop = document.getElementById('confirmDeleteBackdrop');
      const deleteForm = document.getElementById('deleteForm');
      
      if (confirmDeleteBackdrop) {
        confirmDeleteBackdrop.style.display = 'none';
        confirmDeleteBackdrop.setAttribute('aria-hidden', 'true');
      }
      
      if (deleteForm) {
        console.log("Submitting delete form");
        deleteForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
      }
      
      // Reset flag after a delay
      setTimeout(() => { isDeleting = false; }, 2000);
    }
  });

  if (toggleEditBtn) {
    toggleEditBtn.addEventListener('click', () => setEditMode(!editMode));
  }


  // ============================
  // 3) SLIDERS (Filters)
  // ============================
  const kcalRange = document.getElementById('kcalRange');
  const protRange = document.getElementById('protRange');
  const kcalVal = document.getElementById('kcalVal');
  const protVal = document.getElementById('protVal');

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

  // ============================
  // 4) AJAX FORM SUBMISSION
  // ============================
  function clearErrors(form) {
    form.querySelectorAll('.errors').forEach(div => {
      div.textContent = '';
    });
  }

  function showErrors(form, errors) {
    const isModalForm = form.classList.contains('recipe-ajax-form') && form.id === 'updateForm';
    const prefix = isModalForm ? 'f-error-' : 'error-';

    for (const [field, message] of Object.entries(errors)) {
      const errorDiv = document.getElementById(prefix + field);
      if (errorDiv) {
        errorDiv.textContent = message;
      } else {
        // Fallback for fields that might not have a prefixed ID in both places
        const alternativeErrorDiv = document.getElementById('error-' + field);
        if (alternativeErrorDiv) alternativeErrorDiv.textContent = message;
      }
    }
  }

  // Track forms being submitted to prevent double submission
  const submittingForms = new Set();

  document.querySelectorAll('.recipe-ajax-form').forEach(form => {
    // Skip if listener already attached (check data attribute)
    if (form.dataset.listenerAttached === 'true') {
      console.log("Listener already attached to form:", form.id);
      return;
    }
    
    // Mark as having listener
    form.dataset.listenerAttached = 'true';
    console.log("Attaching listener to form:", form.id);
    
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      
      // Prevent double submission
      const formKey = form.id || form;
      if (submittingForms.has(formKey)) {
        console.log("Form already submitting, ignoring");
        return;
      }
      
      submittingForms.add(formKey);
      clearErrors(form);
      console.log("Submitting form:", form.id);
      console.log("Form action:", form.action);
      console.log("Form method:", form.method);

      let submitBtn = form.querySelector('button[type="submit"]');
      if (!submitBtn && form.id) {
        submitBtn = document.querySelector(`button[type="submit"][form="${form.id}"]`);
      }

      const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';
      }

      // FormData naturally picks up all inputs INSIDE the form
      const formData = new FormData(form);
      
      // Log FormData contents
      console.log("FormData contents:");
      for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: ${value}`);
      }

      // ✅ Handle elements outside the form but linked via "form" attribute (for fallback)
      if (form.id) {
        document.querySelectorAll(`[form="${form.id}"]`).forEach(el => {
          if (el.name && !form.contains(el)) {
            if (el.type === 'checkbox' || el.type === 'radio') {
              if (el.checked) formData.append(el.name, el.value);
            } else if (el.type === 'file') {
              if (el.files && el.files[0]) formData.append(el.name, el.files[0]);
            } else {
              formData.append(el.name, el.value);
            }
          }
        });
      }

      const url = form.action || window.location.href;
      console.log("Submitting to URL:", url);
      
      try {
        const response = await fetch(url, {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin' // ✅ envoie les cookies/session Symfony
        });
        
        console.log("Response status:", response.status);
        console.log("Response ok:", response.ok);
        
        if (!response.ok) {
          const text = await response.text();
          console.error('Server error response:', text);

          if (text.trim().startsWith('<')) {
            alert("403: accès refusé / token CSRF invalide. Vérifie ACTION et TOKEN dans console.");
            return;
          }

          try {
            const errorJson = JSON.parse(text);
            showErrors(form, errorJson.errors || { global: 'Server error' });
          } catch {
            alert('Server error. Check console.');
          }
          return;
        }

        const result = await response.json();
        console.log("Result:", result);

        if (result.success) {
          console.log("Success! Redirecting...");
          // If it's the create form, redirect directly without alert
          if (form.id === 'recipe-ajax-form' || form.closest('#recipe-form-container')) {
            // Redirect to the recipes page
            window.location.href = window.location.pathname;
            return; // Stop execution
          }
          
          alert(result.message || 'Success!');
          window.location.reload();
        } else {
          console.error("Submission failed:", result.errors);
          // Show error message to user
          const errorMsg = result.errors?.global || Object.values(result.errors || {}).join(', ') || 'An error occurred';
          alert('Error: ' + errorMsg);
          showErrors(form, result.errors || {});
        }
      } catch (err) {
        console.error('Submission error:', err);
        alert('An unexpected error occurred: ' + err.message);
      }
      finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnText;
        }
        // Remove from submitting set
        const formKey = form.id || form;
        submittingForms.delete(formKey);
      }
    }, { once: false }); // Explicitly set once to false but we track manually
  });


});
