// Modern Antigravity Tailwind UI Controller for Kepegawaian SIPIT

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Sidebar Toggle & Overlay
    const mobileToggleBtn = document.getElementById('mobileSidebarToggle');
    const closeMobileSidebarBtn = document.getElementById('closeMobileSidebarBtn');
    const mobileBackdrop = document.getElementById('mobileSidebarBackdrop');
    const appSidebar = document.getElementById('appSidebar');

    const openMobileSidebar = () => {
        if (appSidebar && mobileBackdrop) {
            appSidebar.classList.remove('-translate-x-full');
            appSidebar.classList.add('translate-x-0');
            mobileBackdrop.classList.remove('hidden');
            setTimeout(() => {
                mobileBackdrop.classList.remove('opacity-0');
                mobileBackdrop.classList.add('opacity-100');
            }, 10);
            document.body.classList.add('overflow-hidden');
        }
    };

    const closeMobileSidebar = () => {
        if (appSidebar && mobileBackdrop) {
            appSidebar.classList.remove('translate-x-0');
            appSidebar.classList.add('-translate-x-full');
            mobileBackdrop.classList.remove('opacity-100');
            mobileBackdrop.classList.add('opacity-0');
            setTimeout(() => {
                mobileBackdrop.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }, 300);
        }
    };

    if (mobileToggleBtn) {
        mobileToggleBtn.addEventListener('click', openMobileSidebar);
    }
    if (closeMobileSidebarBtn) {
        closeMobileSidebarBtn.addEventListener('click', closeMobileSidebar);
    }
    if (mobileBackdrop) {
        mobileBackdrop.addEventListener('click', closeMobileSidebar);
    }

    // 2. Desktop Sidebar Collapse Toggle
    const desktopToggleBtn = document.getElementById('desktopSidebarToggle');
    const appWrapper = document.getElementById('appMainWrapper');

    const toggleDesktopSidebar = () => {
        // Close all open flyouts before toggling
        document.querySelectorAll('.has-submenu.flyout-open').forEach(el => el.classList.remove('flyout-open'));

        if (document.body.classList.contains('sidebar-collapsed')) {
            document.body.classList.remove('sidebar-collapsed');
            localStorage.setItem('sipit_sidebar_state', 'expanded');
        } else {
            document.body.classList.add('sidebar-collapsed');
            localStorage.setItem('sipit_sidebar_state', 'collapsed');
        }
    };

    if (desktopToggleBtn) {
        desktopToggleBtn.addEventListener('click', toggleDesktopSidebar);
    }

    // Restore desktop sidebar preference
    if (localStorage.getItem('sipit_sidebar_state') === 'collapsed' && window.innerWidth >= 1024) {
        document.body.classList.add('sidebar-collapsed');
    }

    // 3. Sidebar Submenu Accordions & Flyouts
    const accordionTriggers = document.querySelectorAll('[data-sidebar-toggle]');
    accordionTriggers.forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const parentSubmenu = trigger.closest('.has-submenu');
            const targetId = trigger.getAttribute('data-sidebar-toggle');
            const targetSubmenu = document.getElementById(targetId);
            const chevron = trigger.querySelector('.menu-chevron');

            // If sidebar is currently collapsed on desktop: toggle flyout
            if (document.body.classList.contains('sidebar-collapsed') && window.innerWidth >= 1024) {
                if (parentSubmenu) {
                    const isFlyoutOpen = parentSubmenu.classList.contains('flyout-open');
                    // Close all other open flyouts
                    document.querySelectorAll('.has-submenu.flyout-open').forEach(el => el.classList.remove('flyout-open'));
                    if (!isFlyoutOpen) {
                        parentSubmenu.classList.add('flyout-open');
                    }
                }
                return;
            }

            // Normal accordion toggle when sidebar is expanded
            if (targetSubmenu) {
                const isExpanded = !targetSubmenu.classList.contains('hidden');
                if (isExpanded) {
                    targetSubmenu.classList.add('hidden');
                    if (chevron) chevron.classList.remove('rotate-180');
                    trigger.setAttribute('aria-expanded', 'false');
                } else {
                    targetSubmenu.classList.remove('hidden');
                    if (chevron) chevron.classList.add('rotate-180');
                    trigger.setAttribute('aria-expanded', 'true');
                }
            }
        });
    });

    // Close flyouts when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.has-submenu')) {
            document.querySelectorAll('.has-submenu.flyout-open').forEach(el => el.classList.remove('flyout-open'));
        }
    });

    // 4. User Profile Dropdown
    const userMenuBtn = document.getElementById('userMenuButton');
    const userMenuDropdown = document.getElementById('userMenuDropdown');

    if (userMenuBtn && userMenuDropdown) {
        userMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = !userMenuDropdown.classList.contains('hidden');
            if (isOpen) {
                userMenuDropdown.classList.add('hidden');
            } else {
                userMenuDropdown.classList.remove('hidden');
            }
        });

        document.addEventListener('click', (e) => {
            if (!userMenuDropdown.contains(e.target) && !userMenuBtn.contains(e.target)) {
                userMenuDropdown.classList.add('hidden');
            }
        });
    }

    // 5. Global loading indicator for forms with id createForm & editForm
    const setupSubmitButtonSpinner = (formId, btnId, loadingText) => {
        const form = document.getElementById(formId);
        const submitBtn = document.getElementById(btnId);
        if (form && submitBtn) {
            form.addEventListener('submit', () => {
                submitBtn.disabled = true;
                submitBtn.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg> ${loadingText}
                `;
            });
        }
    };

    setupSubmitButtonSpinner('createForm', 'submitBtn', 'Menyimpan...');
    setupSubmitButtonSpinner('editForm', 'submitBtn', 'Memperbarui...');

    // 6. Global Table Row Action Dropdown (three-dot menu with viewport-safe positioning)
    const closeAllActionDropdowns = (exceptMenu = null) => {
        document.querySelectorAll('.action-dropdown-menu.is-open').forEach((menu) => {
            if (menu === exceptMenu) {
                return;
            }
            menu.classList.remove('is-open');
            const toggle = menu.closest('.action-dropdown')?.querySelector('.action-dropdown-toggle');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    };

    const positionActionMenu = (toggle, menu) => {
        const margin = 8;
        const gap = 6;
        const rect = toggle.getBoundingClientRect();

        menu.style.visibility = 'hidden';
        menu.classList.add('is-open');

        const menuWidth = menu.offsetWidth || 184;
        const menuHeight = menu.offsetHeight || 120;

        let left = rect.right - menuWidth;
        if (left < margin) {
            left = margin;
        }
        if (left + menuWidth > window.innerWidth - margin) {
            left = Math.max(margin, window.innerWidth - menuWidth - margin);
        }

        const spaceBelow = window.innerHeight - rect.bottom - margin;
        const spaceAbove = rect.top - margin;
        let top = rect.bottom + gap;
        if (spaceBelow < menuHeight && spaceAbove > spaceBelow) {
            top = Math.max(margin, rect.top - menuHeight - gap);
        } else if (top + menuHeight > window.innerHeight - margin) {
            top = Math.max(margin, window.innerHeight - menuHeight - margin);
        }

        menu.style.left = `${left}px`;
        menu.style.top = `${top}px`;
        menu.style.visibility = '';
    };

    document.addEventListener('click', (e) => {
        const toggle = e.target.closest('.action-dropdown-toggle');
        if (toggle) {
            e.preventDefault();
            e.stopPropagation();

            const wrapper = toggle.closest('.action-dropdown');
            const menu = wrapper?.querySelector('.action-dropdown-menu');
            if (!menu) {
                return;
            }

            const willOpen = !menu.classList.contains('is-open');
            closeAllActionDropdowns(willOpen ? menu : null);

            if (willOpen) {
                positionActionMenu(toggle, menu);
                toggle.setAttribute('aria-expanded', 'true');
            } else {
                menu.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
            return;
        }

        const clickedInsideMenu = e.target.closest('.action-dropdown-menu');
        if (!clickedInsideMenu || e.target.closest('.delete-button')) {
            closeAllActionDropdowns();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAllActionDropdowns();
        }
    });

    window.addEventListener('resize', () => closeAllActionDropdowns());
    window.addEventListener('scroll', () => closeAllActionDropdowns(), { capture: true, passive: true });

    // 7. 24-Hour Time Input Masking & Popover Picker (HH:MM, 00:00 - 23:59)
    const time24Regex = /^([01]\d|2[0-3]):[0-5]\d$/;

    const normalizeTime24Value = (rawValue) => {
        const trimmed = String(rawValue || '').trim();
        if (!trimmed) {
            return '';
        }

        const colonMatch = trimmed.match(/^(\d{1,2}):(\d{1,2})(?::\d{2})?$/);
        if (colonMatch) {
            const hh = Number(colonMatch[1]);
            const mm = Number(colonMatch[2]);
            if (hh >= 0 && hh <= 23 && mm >= 0 && mm <= 59) {
                return `${String(hh).padStart(2, '0')}:${String(mm).padStart(2, '0')}`;
            }

            return null;
        }

        const digitsOnly = trimmed.replace(/\D/g, '');
        if (digitsOnly.length === 4) {
            const hh = Number(digitsOnly.slice(0, 2));
            const mm = Number(digitsOnly.slice(2, 4));
            if (hh >= 0 && hh <= 23 && mm >= 0 && mm <= 59) {
                return `${String(hh).padStart(2, '0')}:${String(mm).padStart(2, '0')}`;
            }

            return null;
        }

        if (digitsOnly.length === 3) {
            const hh = Number(digitsOnly.slice(0, 1));
            const mm = Number(digitsOnly.slice(1, 3));
            if (hh >= 0 && hh <= 23 && mm >= 0 && mm <= 59) {
                return `${String(hh).padStart(2, '0')}:${String(mm).padStart(2, '0')}`;
            }

            return null;
        }

        if (digitsOnly.length === 1 || digitsOnly.length === 2) {
            const hh = Number(digitsOnly);
            if (hh >= 0 && hh <= 23) {
                return `${String(hh).padStart(2, '0')}:00`;
            }

            return null;
        }

        return null;
    };

    const syncTime24Validity = (input) => {
        const val = input.value.trim();
        if (val === '' || time24Regex.test(val)) {
            input.setCustomValidity('');
        } else {
            input.setCustomValidity('Gunakan format 24 jam yang valid (00:00 - 23:59).');
        }
    };

    let activeTimeInput = null;
    let timePopover = null;

    const ensureTimePopover = () => {
        if (timePopover) {
            return timePopover;
        }

        const pop = document.createElement('div');
        pop.className = 'time24-popover';
        pop.setAttribute('role', 'dialog');
        pop.setAttribute('aria-label', 'Pemilih Waktu 24 Jam');

        const hourButtons = Array.from({ length: 24 }, (_, h) => {
            const val = String(h).padStart(2, '0');
            return `<button type="button" class="time24-option" data-hour="${val}">${val}</button>`;
        }).join('');

        const minuteButtons = Array.from({ length: 60 }, (_, m) => {
            const val = String(m).padStart(2, '0');
            return `<button type="button" class="time24-option" data-minute="${val}">${val}</button>`;
        }).join('');

        pop.innerHTML = `
            <div class="time24-popover-header">
                <span>Format 24 Jam</span>
                <span class="time24-popover-preview" data-time24-preview>07:00</span>
            </div>
            <div class="time24-popover-grid">
                <div class="time24-col">
                    <div class="time24-col-label">Jam</div>
                    <div class="time24-col-list" data-time24-hours>${hourButtons}</div>
                </div>
                <div class="time24-col">
                    <div class="time24-col-label">Menit</div>
                    <div class="time24-col-list" data-time24-minutes>${minuteButtons}</div>
                </div>
            </div>
            <div class="time24-popover-footer">
                <button type="button" class="time24-done-btn" data-time24-done>Selesai</button>
            </div>
        `;

        pop.addEventListener('click', (e) => {
            if (!activeTimeInput) {
                return;
            }

            const hourBtn = e.target.closest('[data-hour]');
            const minuteBtn = e.target.closest('[data-minute]');
            const doneBtn = e.target.closest('[data-time24-done]');

            if (doneBtn) {
                closeTimePopover();
                return;
            }

            if (!hourBtn && !minuteBtn) {
                return;
            }

            const normalized = normalizeTime24Value(activeTimeInput.value) || '07:00';
            let [currentHour, currentMinute] = normalized.split(':');

            if (hourBtn) {
                currentHour = hourBtn.dataset.hour;
            }
            if (minuteBtn) {
                currentMinute = minuteBtn.dataset.minute;
            }

            activeTimeInput.value = `${currentHour}:${currentMinute}`;
            syncTime24Validity(activeTimeInput);
            updatePopoverSelection(currentHour, currentMinute);

            activeTimeInput.dispatchEvent(new Event('input', { bubbles: true }));
            activeTimeInput.dispatchEvent(new Event('change', { bubbles: true }));
        });

        document.body.appendChild(pop);
        timePopover = pop;
        return pop;
    };

    const updatePopoverSelection = (hour, minute) => {
        if (!timePopover) {
            return;
        }

        const preview = timePopover.querySelector('[data-time24-preview]');
        if (preview) {
            preview.textContent = `${hour}:${minute}`;
        }

        timePopover.querySelectorAll('[data-hour]').forEach((btn) => {
            const selected = btn.dataset.hour === hour;
            btn.classList.toggle('is-selected', selected);
            if (selected) {
                const list = btn.parentElement;
                if (list) {
                    list.scrollTop = Math.max(0, btn.offsetTop - (list.clientHeight / 2) + (btn.offsetHeight / 2));
                }
            }
        });

        timePopover.querySelectorAll('[data-minute]').forEach((btn) => {
            const selected = btn.dataset.minute === minute;
            btn.classList.toggle('is-selected', selected);
            if (selected) {
                const list = btn.parentElement;
                if (list) {
                    list.scrollTop = Math.max(0, btn.offsetTop - (list.clientHeight / 2) + (btn.offsetHeight / 2));
                }
            }
        });
    };

    const positionTimePopover = (fieldWrapper, pop) => {
        const rect = fieldWrapper.getBoundingClientRect();
        pop.style.visibility = 'hidden';
        pop.classList.add('is-open');

        const popWidth = pop.offsetWidth || 224;
        const popHeight = pop.offsetHeight || 280;
        const margin = 8;

        let left = rect.left;
        if (left + popWidth > window.innerWidth - margin) {
            left = Math.max(margin, window.innerWidth - popWidth - margin);
        }

        let top = rect.bottom + 4;
        if (top + popHeight > window.innerHeight - margin && rect.top - popHeight - 4 > margin) {
            top = rect.top - popHeight - 4;
        } else if (top + popHeight > window.innerHeight - margin) {
            top = Math.max(margin, window.innerHeight - popHeight - margin);
        }

        pop.style.left = `${left}px`;
        pop.style.top = `${top}px`;
        pop.style.visibility = '';
    };

    const closeTimePopover = () => {
        if (timePopover) {
            timePopover.classList.remove('is-open');
        }
        activeTimeInput = null;
    };

    const openTimePopover = (input) => {
        if (input.readOnly || input.disabled) {
            return;
        }

        const fieldWrapper = input.closest('.time24-field') || input;
        const pop = ensureTimePopover();

        if (activeTimeInput === input && pop.classList.contains('is-open')) {
            closeTimePopover();
            return;
        }

        activeTimeInput = input;
        positionTimePopover(fieldWrapper, pop);

        const normalized = normalizeTime24Value(input.value) || '07:00';
        const [hour, minute] = normalized.split(':');
        updatePopoverSelection(hour, minute);
    };

    document.addEventListener('input', (e) => {
        const input = e.target.closest('input[data-time24="true"]');
        if (!input || !e.isTrusted) {
            return;
        }

        if (e.inputType && e.inputType.startsWith('delete')) {
            syncTime24Validity(input);
            return;
        }

        let cleaned = input.value.replace(/[^\d:]/g, '');
        const firstColon = cleaned.indexOf(':');
        if (firstColon !== -1) {
            const hPart = cleaned.slice(0, firstColon).slice(0, 2);
            const mPart = cleaned.slice(firstColon + 1).replace(/:/g, '').slice(0, 2);
            cleaned = `${hPart}:${mPart}`;
        } else {
            const digits = cleaned.replace(/\D/g, '').slice(0, 4);
            if (digits.length === 2 && e.inputType === 'insertText') {
                cleaned = `${digits}:`;
            } else if (digits.length > 2) {
                cleaned = `${digits.slice(0, 2)}:${digits.slice(2, 4)}`;
            } else {
                cleaned = digits;
            }
        }

        if (input.value !== cleaned) {
            input.value = cleaned;
        }

        syncTime24Validity(input);
    });

    document.addEventListener('blur', (e) => {
        const input = e.target.closest('input[data-time24="true"]');
        if (!input) {
            return;
        }

        const raw = input.value.trim();
        if (raw === '') {
            input.setCustomValidity('');
            return;
        }

        const normalized = normalizeTime24Value(raw);
        if (normalized !== null) {
            if (input.value !== normalized) {
                input.value = normalized;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
            input.setCustomValidity('');
        } else {
            input.setCustomValidity('Gunakan format 24 jam yang valid (00:00 - 23:59).');
        }
    }, true);

    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('.time24-trigger');
        if (trigger) {
            e.preventDefault();
            e.stopPropagation();

            const wrapper = trigger.closest('.time24-field');
            const input = wrapper?.querySelector('input[data-time24="true"]');
            if (input) {
                openTimePopover(input);
            }
            return;
        }

        if (!e.target.closest('.time24-popover') && !e.target.closest('.time24-field')) {
            closeTimePopover();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeTimePopover();
        }
    });

    window.addEventListener('resize', () => closeTimePopover());
    window.addEventListener('scroll', (e) => {
        if (timePopover && !timePopover.contains(e.target)) {
            closeTimePopover();
        }
    }, { capture: true, passive: true });
});
