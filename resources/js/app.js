import 'bootstrap';
import $ from 'jquery';

import intlTelInput from 'intl-tel-input';
import 'intl-tel-input/build/css/intlTelInput.css';
import utilsScript from 'intl-tel-input/build/js/utils.js';

window.$ = $;
window.intlTelInput = intlTelInput.default || intlTelInput;
window.utilsScript = utilsScript;
