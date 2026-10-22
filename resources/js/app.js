import Alpine from 'alpinejs';
import { registerForms } from './forms';
import { registerPasswordToggles } from './password-toggle';

window.Alpine = Alpine;

registerForms(Alpine);
registerPasswordToggles();

Alpine.start();
