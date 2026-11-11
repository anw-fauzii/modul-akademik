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
});
