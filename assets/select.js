/**
 * Modern Select Dropdown - shadcn-inspired vanilla JS
 * Converts native <select> elements into styled dropdowns
 */

class SelectComponent {
  constructor(selectEl, options = {}) {
    this.selectEl = selectEl;
    this.options = options;
    this.wrapper = null;
    this.trigger = null;
    this.content = null;
    this.isOpen = false;

    this.init();
  }

  init() {
    // Get config from data attributes or defaults
    this.config = {
      placeholder: this.selectEl.dataset.placeholder || 'Select an option',
      size: this.selectEl.dataset.size || 'md', // sm, md, lg
      disabled: this.selectEl.disabled,
      fullWidth: this.selectEl.dataset.fullWidth !== undefined,
    };

    this.createWrapper();
    this.createTrigger();
    this.createContent();
    this.hideNativeSelect();
    this.attachEventListeners();
    this.updateValue();
  }

  createWrapper() {
    this.wrapper = document.createElement('div');
    this.wrapper.className = 'select-wrapper' + (this.config.fullWidth ? ' full-width' : '');
    this.wrapper.dataset.selectId = this.selectEl.id || Math.random().toString(36).substr(2, 9);
    this.selectEl.parentNode.insertBefore(this.wrapper, this.selectEl);
    this.wrapper.appendChild(this.selectEl);
  }

  createTrigger() {
    this.trigger = document.createElement('button');
    this.trigger.type = 'button';
    this.trigger.className = `select-trigger size-${this.config.size}${this.config.disabled ? ' disabled' : ''}`;
    this.trigger.setAttribute('role', 'combobox');
    this.trigger.setAttribute('aria-expanded', 'false');
    this.trigger.setAttribute('aria-haspopup', 'listbox');
    this.trigger.setAttribute('aria-labelledby', this.selectEl.id ? `${this.selectEl.id}-label` : undefined);

    this.trigger.innerHTML = `
      <span class="select-value"><span class="select-placeholder-text">${this.config.placeholder}</span></span>
      <span class="select-chevron">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
      </span>
    `;

    this.wrapper.appendChild(this.trigger);
  }

  createContent() {
    this.content = document.createElement('div');
    this.content.className = 'select-content';
    this.content.setAttribute('role', 'listbox');

    const items = this.getOptions();
    items.forEach((opt, index) => {
      if (opt.type === 'separator') {
        const sep = document.createElement('div');
        sep.className = 'select-separator';
        this.content.appendChild(sep);
      } else if (opt.label) {
        const item = document.createElement('div');
        item.className = 'select-item' + (opt.disabled ? ' disabled' : '') + (opt.selected ? ' selected' : '');
        item.setAttribute('role', 'option');
        item.setAttribute('data-value', opt.value);
        item.setAttribute('data-index', index);
        if (opt.selected) item.setAttribute('aria-selected', 'true');
        if (opt.disabled) item.setAttribute('aria-disabled', 'true');

        item.innerHTML = `
          <span class="select-item-text">${opt.label}</span>
          ${opt.selected ? '<span class="select-item-indicator"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg></span>' : ''}
        `;

        if (!opt.disabled) {
          item.addEventListener('click', () => this.selectOption(opt.value, index));
        }

        this.content.appendChild(item);
      }
    });

    this.wrapper.appendChild(this.content);
  }

  getOptions() {
    const items = [];
    const optgroups = this.selectEl.querySelectorAll('optgroup');

    if (optgroups.length > 0) {
      optgroups.forEach(group => {
        if (group.label) {
          items.push({ type: 'label', label: group.label });
        }
        group.querySelectorAll('option').forEach(opt => {
          items.push({
            value: opt.value,
            label: opt.textContent,
            selected: opt.selected,
            disabled: opt.disabled,
          });
        });
      });
    } else {
      this.selectEl.querySelectorAll('option').forEach(opt => {
        if (opt.dataset.separator) {
          items.push({ type: 'separator' });
        } else {
          items.push({
            value: opt.value,
            label: opt.textContent,
            selected: opt.selected,
            disabled: opt.disabled,
          });
        }
      });
    }

    return items;
  }

  hideNativeSelect() {
    this.selectEl.classList.add('select-native');
    this.selectEl.tabIndex = -1;
  }

  attachEventListeners() {
    // Toggle on trigger click
    this.trigger.addEventListener('click', (e) => {
      if (this.config.disabled) return;
      e.stopPropagation();
      this.toggle();
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
      if (!this.wrapper.contains(e.target)) {
        this.close();
      }
    });

    // Keyboard navigation
    this.trigger.addEventListener('keydown', (e) => {
      if (this.config.disabled) return;

      switch (e.key) {
        case 'Enter':
        case ' ':
          e.preventDefault();
          this.toggle();
          break;
        case 'ArrowDown':
          e.preventDefault();
          if (!this.isOpen) {
            this.open();
          } else {
            this.focusNextItem(1);
          }
          break;
        case 'ArrowUp':
          e.preventDefault();
          if (!this.isOpen) {
            this.open();
          } else {
            this.focusNextItem(-1);
          }
          break;
        case 'Escape':
          e.preventDefault();
          this.close();
          this.trigger.focus();
          break;
        case 'Enter':
          if (this.isOpen) {
            const focused = this.content.querySelector('.select-item:focus');
            if (focused) {
              const value = focused.dataset.value;
              const index = parseInt(focused.dataset.index);
              this.selectOption(value, index);
            }
          }
          break;
      }
    });

    // Sync with native select changes
    this.selectEl.addEventListener('change', () => this.updateValue());
  }

  toggle() {
    if (this.isOpen) {
      this.close();
    } else {
      this.open();
    }
  }

  open() {
    this.isOpen = true;
    this.trigger.classList.add('open');
    this.trigger.setAttribute('aria-expanded', 'true');
    this.content.classList.add('open');

    // Scroll selected item into view
    const selected = this.content.querySelector('.select-item.selected');
    if (selected) {
      selected.scrollIntoView({ block: 'nearest' });
    }
  }

  close() {
    this.isOpen = false;
    this.trigger.classList.remove('open');
    this.trigger.setAttribute('aria-expanded', 'false');
    this.content.classList.remove('open');
  }

  selectOption(value, index) {
    // Update native select
    this.selectEl.value = value;

    // Trigger change event on native select
    const event = new Event('change', { bubbles: true });
    this.selectEl.dispatchEvent(event);

    // Update UI
    this.updateValue();
    this.close();

    // Update selected states
    this.content.querySelectorAll('.select-item').forEach(item => {
      item.classList.remove('selected');
      item.removeAttribute('aria-selected');
    });

    const selectedItem = this.content.querySelector(`[data-value="${CSS.escape(value)}"]`);
    if (selectedItem) {
      selectedItem.classList.add('selected');
      selectedItem.setAttribute('aria-selected', 'true');
    }
  }

  updateValue() {
    const selected = this.selectEl.querySelector('option:checked') || this.selectEl.querySelector('option[selected]');
    const valueEl = this.trigger.querySelector('.select-value');

    if (selected && selected.value) {
      this.trigger.classList.remove('placeholder');
      this.trigger.classList.add('has-value');
      valueEl.innerHTML = `<span>${selected.textContent}</span>`;
    } else {
      this.trigger.classList.add('placeholder');
      this.trigger.classList.remove('has-value');
      valueEl.innerHTML = `<span class="select-placeholder-text">${this.config.placeholder}</span>`;
    }
  }

  focusNextItem(direction) {
    const items = Array.from(this.content.querySelectorAll('.select-item:not(.disabled)'));
    const focused = this.content.querySelector('.select-item:focus');
    const currentIndex = focused ? items.indexOf(focused) : -1;
    let nextIndex = currentIndex + direction;

    if (nextIndex < 0) nextIndex = items.length - 1;
    if (nextIndex >= items.length) nextIndex = 0;

    items[nextIndex]?.focus();
  }

  destroy() {
    // Restore native select
    this.selectEl.classList.remove('select-native');
    this.selectEl.tabIndex = 0;

    // Remove created elements
    if (this.trigger) this.trigger.remove();
    if (this.content) this.content.remove();
    if (this.wrapper) {
      this.wrapper.parentNode.insertBefore(this.selectEl, this.wrapper);
      this.wrapper.remove();
    }
  }
}

/**
 * Initialize all selects with data-select="true" attribute
 * Or call SelectComponent.init() on individual elements
 */
function initSelects(selector = 'select[data-select="true"]') {
  const selects = document.querySelectorAll(selector);
  selects.forEach(el => {
    if (!el.dataset.initialized) {
      new SelectComponent(el);
      el.dataset.initialized = 'true';
    }
  });
}

/**
 * Auto-init on DOM ready
 */
if (typeof document !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initSelects());
  } else {
    initSelects();
  }
}

// Export for manual use
if (typeof window !== 'undefined') {
  window.SelectComponent = SelectComponent;
  window.initSelects = initSelects;
}
