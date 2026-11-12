@php
    $akModalId = $modalId ?? 'tambahAnggotaModal';
    $akSearchInputId = $searchInputId ?? 'filterSiswaInput';
@endphp

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalId = @json($akModalId);
            const searchInputId = @json($akSearchInputId);
            const modal = document.getElementById(modalId);
            if (!modal) {
                return;
            }

            const openButtons = document.querySelectorAll(`[data-open-ak-modal="${modalId}"]`);
            const closeButtons = modal.querySelectorAll(`[data-close-ak-modal="${modalId}"]`);
            const searchInput = modal.querySelector(`#${searchInputId}`);

            const openModal = () => {
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                if (searchInput) {
                    setTimeout(() => searchInput.focus(), 50);
                }
            };

            const closeModal = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            };

            if (modal.classList.contains('is-open')) {
                document.body.style.overflow = 'hidden';
            }

            openButtons.forEach((btn) => btn.addEventListener('click', openModal));
            closeButtons.forEach((btn) => btn.addEventListener('click', closeModal));

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeModal();
                }
            });
        });
    </script>
@endonce

