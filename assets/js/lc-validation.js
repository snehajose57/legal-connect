/**
 * lc-validation.js - Legal Connect Real-Time Form Validation Library
 * Handles: register.php, user/payment.php
 */
(function () {
    'use strict';

    /* ── HELPERS ── */
    function getOrCreateFeedback(inputEl, feedbackId) {
        var fb = document.getElementById(feedbackId);
        if (!fb) {
            fb = document.createElement('div');
            fb.id = feedbackId;
            fb.className = 'lc-feedback';
            inputEl.parentNode.appendChild(fb);
        }
        return fb;
    }
    function setValid(inputEl, fb) {
        inputEl.classList.remove('is-invalid');
        inputEl.classList.add('is-valid');
        if (fb) { fb.textContent = ''; fb.className = 'lc-feedback'; }
    }
    function setInvalid(inputEl, fb, msg) {
        inputEl.classList.remove('is-valid');
        inputEl.classList.add('is-invalid');
        if (fb) { fb.textContent = msg; fb.className = 'lc-feedback lc-feedback-error'; }
    }
    function sanitizePaste(e, regex) {
        e.preventDefault();
        var pasted = (e.clipboardData || window.clipboardData).getData('text');
        var clean = pasted.replace(regex, '');
        var el = e.target;
        var start = el.selectionStart, end = el.selectionEnd;
        el.value = el.value.substring(0, start) + clean + el.value.substring(end);
        el.dispatchEvent(new Event('input'));
    }

    /* ── 1. NAME ── */
    function lcValidateName(inputEl, feedbackId) {
        var fb = getOrCreateFeedback(inputEl, feedbackId);
        function filterName() {
            var pos = inputEl.selectionStart;
            var prev = inputEl.value;
            var cleaned = prev.replace(/[^a-zA-Z ]/g, '');
            if (cleaned !== prev) {
                inputEl.value = cleaned;
                var np = Math.max(0, pos - (prev.length - cleaned.length));
                inputEl.setSelectionRange(np, np);
            }
            return validateName();
        }
        function validateName() {
            var val = inputEl.value.trim();
            if (val === '') { setInvalid(inputEl, fb, 'Full name is required.'); return false; }
            if (val.length < 2) { setInvalid(inputEl, fb, 'Name must be at least 2 characters.'); return false; }
            setValid(inputEl, fb); return true;
        }
        inputEl.addEventListener('input', filterName);
        inputEl.addEventListener('paste', function (e) { sanitizePaste(e, /[^a-zA-Z ]/g); });
        inputEl.addEventListener('blur', validateName);
        inputEl._lcValidate = validateName;
    }

    /* ── 2. PHONE ── */
    function lcValidatePhone(inputEl, feedbackId) {
        var fb = getOrCreateFeedback(inputEl, feedbackId);
        function filterPhone() {
            var pos = inputEl.selectionStart;
            var prev = inputEl.value;
            var cleaned = prev.replace(/[^0-9]/g, '').substring(0, 10);
            if (cleaned !== prev) {
                inputEl.value = cleaned;
                var np = Math.max(0, pos - (prev.length - cleaned.length));
                inputEl.setSelectionRange(np, np);
            }
            return validatePhone();
        }
        function validatePhone() {
            var val = inputEl.value.trim();
            if (val === '') { setInvalid(inputEl, fb, 'Phone number is required.'); return false; }
            if (!/^[0-9]{10}$/.test(val)) { setInvalid(inputEl, fb, 'Enter a valid 10-digit phone number.'); return false; }
            setValid(inputEl, fb); return true;
        }
        inputEl.addEventListener('input', filterPhone);
        inputEl.addEventListener('paste', function (e) { sanitizePaste(e, /[^0-9]/g); });
        inputEl.addEventListener('blur', validatePhone);
        inputEl._lcValidate = validatePhone;
    }

    /* ── 3. EMAIL ── */
    function lcValidateEmail(inputEl, feedbackId) {
        var fb = getOrCreateFeedback(inputEl, feedbackId);
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
        function validateEmail() {
            var val = inputEl.value.trim();
            if (val === '') { setInvalid(inputEl, fb, 'Email address is required.'); return false; }
            if (!emailRegex.test(val)) { setInvalid(inputEl, fb, 'Please enter a valid email address.'); return false; }
            setValid(inputEl, fb); return true;
        }
        inputEl.addEventListener('input', validateEmail);
        inputEl.addEventListener('blur', validateEmail);
        inputEl._lcValidate = validateEmail;
    }

    /* ── 4. PASSWORD ── */
    function lcValidatePassword(inputEl, feedbackId) {
        var fb = getOrCreateFeedback(inputEl, feedbackId);
        function validatePassword() {
            var val = inputEl.value;
            if (val === '') { setInvalid(inputEl, fb, 'Password is required.'); return false; }
            if (val.length < 6) { setInvalid(inputEl, fb, 'Password must be at least 6 characters.'); return false; }
            setValid(inputEl, fb); return true;
        }
        inputEl.addEventListener('input', validatePassword);
        inputEl.addEventListener('blur', validatePassword);
        inputEl._lcValidate = validatePassword;
    }

    /* ── 5. CONFIRM PASSWORD ── */
    function lcValidateConfirmPassword(passwordEl, confirmEl, feedbackId) {
        var fb = getOrCreateFeedback(confirmEl, feedbackId);
        function validateConfirm() {
            var val = confirmEl.value;
            if (val === '') { setInvalid(confirmEl, fb, 'Please confirm your password.'); return false; }
            if (val !== passwordEl.value) { setInvalid(confirmEl, fb, 'Passwords do not match.'); return false; }
            setValid(confirmEl, fb); return true;
        }
        confirmEl.addEventListener('input', validateConfirm);
        passwordEl.addEventListener('input', function () { if (confirmEl.value !== '') validateConfirm(); });
        confirmEl.addEventListener('blur', validateConfirm);
        confirmEl._lcValidate = validateConfirm;
    }

    /* ── 6. ADDRESS ── */
    function lcValidateAddress(inputEl, feedbackId, minLen) {
        var fb = getOrCreateFeedback(inputEl, feedbackId);
        minLen = minLen || 5;
        function validateAddress() {
            var val = inputEl.value.trim();
            if (val === '') { setInvalid(inputEl, fb, 'Address is required.'); return false; }
            if (val.length < minLen) { setInvalid(inputEl, fb, 'Please enter a more complete address.'); return false; }
            setValid(inputEl, fb); return true;
        }
        inputEl.addEventListener('blur', validateAddress);
        inputEl.addEventListener('input', function () { if (inputEl.classList.contains('is-invalid')) validateAddress(); });
        inputEl._lcValidate = validateAddress;
    }

    /* ── 7. CARD NUMBER ── */
    function lcValidateCardNumber(inputEl, feedbackId) {
        var fb = getOrCreateFeedback(inputEl, feedbackId);
        function formatCard() {
            var raw = inputEl.value.replace(/\D/g, '').substring(0, 16);
            var formatted = raw.replace(/(.{4})/g, '$1 ').trimEnd();
            inputEl.value = formatted;
            return validateCard();
        }
        function validateCard() {
            var digits = inputEl.value.replace(/\s/g, '');
            if (digits === '') { setInvalid(inputEl, fb, 'Card number is required.'); return false; }
            if (digits.length !== 16) { setInvalid(inputEl, fb, 'Card number must be exactly 16 digits.'); return false; }
            setValid(inputEl, fb); return true;
        }
        inputEl.addEventListener('input', formatCard);
        inputEl.addEventListener('paste', function (e) {
            e.preventDefault();
            var pasted = (e.clipboardData || window.clipboardData).getData('text');
            inputEl.value = pasted.replace(/\D/g, '').substring(0, 16);
            inputEl.dispatchEvent(new Event('input'));
        });
        inputEl.addEventListener('blur', validateCard);
        inputEl._lcValidate = validateCard;
        inputEl._lcRawValue = function () { return inputEl.value.replace(/\s/g, ''); };
    }

    /* ── 8. CARD HOLDER NAME ── */
    function lcValidateCardHolder(inputEl, feedbackId) {
        var fb = getOrCreateFeedback(inputEl, feedbackId);
        function filterHolder() {
            var pos = inputEl.selectionStart;
            var prev = inputEl.value;
            var cleaned = prev.replace(/[^a-zA-Z ]/g, '');
            if (cleaned !== prev) {
                inputEl.value = cleaned;
                var np = Math.max(0, pos - (prev.length - cleaned.length));
                inputEl.setSelectionRange(np, np);
            }
            return validateHolder();
        }
        function validateHolder() {
            var val = inputEl.value.trim();
            if (val === '') { setInvalid(inputEl, fb, 'Card holder name is required.'); return false; }
            if (val.length < 2) { setInvalid(inputEl, fb, 'Please enter the full name on the card.'); return false; }
            setValid(inputEl, fb); return true;
        }
        inputEl.addEventListener('input', filterHolder);
        inputEl.addEventListener('paste', function (e) { sanitizePaste(e, /[^a-zA-Z ]/g); });
        inputEl.addEventListener('blur', validateHolder);
        inputEl._lcValidate = validateHolder;
    }

    /* ── 9. CARD EXPIRY ── */
    function lcValidateCardExpiry(inputEl, feedbackId) {
        var fb = getOrCreateFeedback(inputEl, feedbackId);
        var prevVal = '';
        function formatExpiry() {
            var cur = inputEl.value;
            var raw = cur.replace(/\D/g, '');
            var isDeleting = prevVal.length > cur.length;
            var formatted;
            if (raw.length >= 2 && !isDeleting) {
                formatted = raw.substring(0, 2) + '/' + raw.substring(2, 4);
            } else if (isDeleting && prevVal.slice(-1) === '/') {
                formatted = raw.substring(0, 1);
            } else {
                formatted = raw.substring(0, 4);
            }
            prevVal = formatted;
            inputEl.value = formatted;
            return validateExpiry();
        }
        function validateExpiry() {
            var val = inputEl.value;
            if (val === '') { setInvalid(inputEl, fb, 'Expiry date is required.'); return false; }
            if (!/^\d{2}\/\d{2}$/.test(val)) { setInvalid(inputEl, fb, 'Enter expiry as MM/YY.'); return false; }
            var parts = val.split('/');
            var mm = parseInt(parts[0], 10);
            var yy = parseInt(parts[1], 10);
            if (mm < 1 || mm > 12) { setInvalid(inputEl, fb, 'Invalid month. Must be 01-12.'); return false; }
            var now = new Date();
            var cy = now.getFullYear() % 100;
            var cm = now.getMonth() + 1;
            if (yy < cy || (yy === cy && mm < cm)) { setInvalid(inputEl, fb, 'This card has expired.'); return false; }
            setValid(inputEl, fb); return true;
        }
        inputEl.addEventListener('input', formatExpiry);
        inputEl.addEventListener('paste', function (e) {
            e.preventDefault();
            var pasted = (e.clipboardData || window.clipboardData).getData('text');
            inputEl.value = pasted.replace(/\D/g, '').substring(0, 4);
            inputEl.dispatchEvent(new Event('input'));
        });
        inputEl.addEventListener('keypress', function (e) {
            if (!/[0-9]/.test(e.key) && !['Backspace','Delete','Tab','ArrowLeft','ArrowRight'].includes(e.key)) {
                e.preventDefault();
            }
        });
        inputEl.addEventListener('blur', validateExpiry);
        inputEl._lcValidate = validateExpiry;
    }

    /* ── 10. CVV ── */
    function lcValidateCVV(inputEl, feedbackId) {
        var fb = getOrCreateFeedback(inputEl, feedbackId);
        function filterCVV() {
            var pos = inputEl.selectionStart;
            var prev = inputEl.value;
            var cleaned = prev.replace(/\D/g, '').substring(0, 3);
            if (cleaned !== prev) {
                inputEl.value = cleaned;
                var np = Math.max(0, pos - (prev.length - cleaned.length));
                inputEl.setSelectionRange(np, np);
            }
            return validateCVV();
        }
        function validateCVV() {
            var val = inputEl.value;
            if (val === '') { setInvalid(inputEl, fb, 'CVV is required.'); return false; }
            if (!/^\d{3}$/.test(val)) { setInvalid(inputEl, fb, 'CVV must be exactly 3 digits.'); return false; }
            setValid(inputEl, fb); return true;
        }
        inputEl.addEventListener('input', filterCVV);
        inputEl.addEventListener('paste', function (e) { sanitizePaste(e, /[^0-9]/g); });
        inputEl.addEventListener('blur', validateCVV);
        inputEl._lcValidate = validateCVV;
    }

    /* ── WIRE-UP: REGISTRATION ── */
    function lcAttachRegistrationValidation() {
        var nameEl  = document.getElementById('name');
        var emailEl = document.getElementById('email');
        var phoneEl = document.getElementById('phone');
        var addrEl  = document.getElementById('address');
        var passEl  = document.getElementById('password');
        var confEl  = document.getElementById('confirm_password');
        if (nameEl)  lcValidateName(nameEl,  'fb-name');
        if (emailEl) lcValidateEmail(emailEl, 'fb-email');
        if (phoneEl) lcValidatePhone(phoneEl, 'fb-phone');
        if (addrEl)  lcValidateAddress(addrEl, 'fb-address', 5);
        if (passEl)  lcValidatePassword(passEl, 'fb-password');
        if (passEl && confEl) lcValidateConfirmPassword(passEl, confEl, 'fb-confirm');
    }

    /* ── WIRE-UP: PAYMENT ── */
    function lcAttachPaymentValidation() {
        var cardNumEl    = document.getElementById('card_num');
        var cardHolderEl = document.getElementById('card_holder');
        var cardExpEl    = document.getElementById('card_exp');
        var cardCvvEl    = document.getElementById('card_cvv');
        if (cardNumEl)    lcValidateCardNumber(cardNumEl, 'fb-card-num');
        if (cardHolderEl) lcValidateCardHolder(cardHolderEl, 'fb-card-holder');
        if (cardExpEl)    lcValidateCardExpiry(cardExpEl, 'fb-card-exp');
        if (cardCvvEl)    lcValidateCVV(cardCvvEl, 'fb-card-cvv');
    }

    /* ── SUBMIT GUARD: REGISTRATION ── */
    window.lcRegisterSubmit = function (form) {
        var ids = ['name','email','phone','address','password','confirm_password'];
        var ok = true;
        ids.forEach(function (id) {
            var el = document.getElementById(id);
            if (el && el._lcValidate && el._lcValidate() === false) ok = false;
        });
        if (!ok) {
            var first = form.querySelector('.is-invalid');
            if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return ok;
    };

    /* ── SUBMIT GUARD: PAYMENT ── */
    window.lcPaymentSubmit = function (form) {
        var cardRadio = document.getElementById('payCard');
        if (!cardRadio || !cardRadio.checked) return true;
        var ids = ['card_num','card_holder','card_exp','card_cvv'];
        var ok = true;
        ids.forEach(function (id) {
            var el = document.getElementById(id);
            if (el && el._lcValidate && el._lcValidate() === false) ok = false;
        });
        if (ok) {
            var cardNumEl = document.getElementById('card_num');
            var hiddenRaw = document.getElementById('card_num_raw');
            if (cardNumEl && hiddenRaw && cardNumEl._lcRawValue) {
                hiddenRaw.value = cardNumEl._lcRawValue();
            }
        }
        if (!ok) {
            var first = document.querySelector('#cardFields .is-invalid');
            if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return ok;
    };

    /* ── AUTO-INIT ── */
    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('registerForm'))  lcAttachRegistrationValidation();
        if (document.getElementById('paymentForm'))   lcAttachPaymentValidation();
    });

})();
