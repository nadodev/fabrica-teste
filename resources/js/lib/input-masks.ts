function digits(value: string, limit: number): string {
    return value.replace(/\D/g, '').slice(0, limit);
}

export function formatPostalCode(value: string): string {
    const clean = digits(value, 8);

    return clean.length > 5 ? `${clean.slice(0, 5)}-${clean.slice(5)}` : clean;
}

export function formatPhone(value: string): string {
    const clean = digits(value, 11);

    if (clean.length <= 2) {
        return clean.length ? `(${clean}` : '';
    }

    if (clean.length <= 6) {
        return `(${clean.slice(0, 2)}) ${clean.slice(2)}`;
    }

    if (clean.length <= 10) {
        return `(${clean.slice(0, 2)}) ${clean.slice(2, 6)}-${clean.slice(6)}`;
    }

    return `(${clean.slice(0, 2)}) ${clean.slice(2, 7)}-${clean.slice(7)}`;
}

export function formatDocument(value: string): string {
    const clean = digits(value, 14);

    if (clean.length <= 11) {
        return clean
            .replace(/^(\d{3})(\d)/, '$1.$2')
            .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/, '.$1-$2');
    }

    return clean
        .replace(/^(\d{2})(\d)/, '$1.$2')
        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1/$2')
        .replace(/(\d{4})(\d)/, '$1-$2');
}
