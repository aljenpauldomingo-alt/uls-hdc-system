/**
 * Show/hide buttons for password fields (<x-password-input>). Plain JavaScript with event
 * delegation, so it works on every page (and without Alpine) and for fields added later.
 */

function setVisible(button, input, visible) {
    input.type = visible ? 'text' : 'password';
    const label = visible ? button.dataset.hideLabel : button.dataset.showLabel;
    button.setAttribute('aria-pressed', String(visible));
    button.setAttribute('aria-label', label);
    button.setAttribute('title', label);
    button.querySelector('[data-icon=show]')?.classList.toggle('hidden', visible);
    button.querySelector('[data-icon=hide]')?.classList.toggle('hidden', !visible);
}

export function registerPasswordToggles() {
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-password-toggle]');
        const input = button && document.getElementById(button.getAttribute('aria-controls'));
        if (!input) {
            return;
        }

        event.preventDefault();
        setVisible(button, input, input.type === 'password');
    });

    // Hide every password again before a form is sent, so browsers treat it as a password.
    document.addEventListener('submit', (event) => {
        event.target.querySelectorAll('[data-password-toggle][aria-pressed="true"]').forEach((button) => {
            const input = document.getElementById(button.getAttribute('aria-controls'));
            if (input) {
                setVisible(button, input, false);
            }
        });
    });
}
