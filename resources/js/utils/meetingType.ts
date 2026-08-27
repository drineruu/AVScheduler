export function meetingTypeFromDate(date: string, weekendDays: number[]): string | null {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) {
        return null;
    }

    const parsed = new Date(`${date}T00:00:00`);

    if (Number.isNaN(parsed.getTime())) {
        return null;
    }

    return weekendDays.includes(parsed.getDay()) ? 'Weekend' : 'Midweek';
}
