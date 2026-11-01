import assert from 'node:assert/strict';
import test from 'node:test';
import registrationAge from '../../resources/js/registration-age.js';

const cases = [
    ['no birth date selected', '', false],
    ['fifteenth birthday today', '2011-11-01', true],
    ['sixteen years old', '2010-11-01', true],
    ['day before eighteenth birthday', '2008-11-02', true],
    ['eighteenth birthday today', '2008-11-01', false],
    ['day after eighteenth birthday', '2008-10-31', false],
    ['eighteenth birthday next month', '2008-12-01', true],
    ['adult', '1990-06-15', false],
    ['future birth date', '2026-11-02', false],
    ['invalid date text', 'invalid', false],
    ['incomplete date', '2011-11', false],
    ['invalid calendar date', '2011-02-29', false],
    ['valid leap-day birth date', '2012-02-29', true],
    ['null birth date', null, false],
    ['array birth date', [], false],
];

for (const [description, birthDate, expected] of cases) {
    test(`guardian visibility: ${description}`, () => {
        const form = registrationAge(birthDate, '2008-11-01', '2026-11-01');

        assert.equal(form.needsGuardian, expected);
    });
}

test('guardian visibility updates when the selected birth date changes', () => {
    const form = registrationAge('', '2008-11-01', '2026-11-01');

    assert.equal(form.needsGuardian, false);

    form.birthDate = '2011-11-01';
    assert.equal(form.needsGuardian, true);

    form.birthDate = '2008-11-01';
    assert.equal(form.needsGuardian, false);

    form.birthDate = '';
    assert.equal(form.needsGuardian, false);
});

test('a March birthday is not advanced to February on leap day', () => {
    assert.equal(registrationAge('2010-03-01', '2010-02-28', '2028-02-29').needsGuardian, true);
    assert.equal(registrationAge('2010-02-28', '2010-02-28', '2028-02-29').needsGuardian, false);
});

test('a leap-day birthday in a non-leap year reaches eighteen on March first', () => {
    assert.equal(registrationAge('2008-02-29', '2008-02-28', '2026-02-28').needsGuardian, true);
    assert.equal(registrationAge('2008-02-29', '2008-03-01', '2026-03-01').needsGuardian, false);
});
