

import Alpine from 'alpinejs';
import registrationAge from './registration-age';
import rentalApplications from './rental-applications';

window.Alpine = Alpine;

Alpine.data('registrationAge', registrationAge);
Alpine.data('rentalApplications', rentalApplications);

Alpine.start();
