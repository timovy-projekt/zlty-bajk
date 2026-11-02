

import Alpine from 'alpinejs';
import registrationAge from './registration-age';

window.Alpine = Alpine;

Alpine.data('registrationAge', registrationAge);

Alpine.start();
