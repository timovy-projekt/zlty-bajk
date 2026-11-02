export default function registrationAge(birthDate, adultBirthDate, currentDate) {
    return {
        birthDate,

        get needsGuardian() {
            if (typeof this.birthDate !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(this.birthDate)) {
                return false;
            }

            const parsedDate = new Date(`${this.birthDate}T00:00:00Z`);

            if (Number.isNaN(parsedDate.getTime()) || parsedDate.toISOString().slice(0, 10) !== this.birthDate) {
                return false;
            }

            return this.birthDate > adultBirthDate && this.birthDate <= currentDate;
        },
    };
}
