import rawCountryCatalog from '../../../data/country-catalog.json';

export type CountryCatalogEntry = {
  code: string;
  name: string;
  dialCode: string;
};

const normalizeCountryCatalogEntry = (entry: Partial<CountryCatalogEntry>): CountryCatalogEntry | null => {
  const code = String(entry.code ?? '').trim().toUpperCase();
  const name = String(entry.name ?? '').trim();
  const dialCode = String(entry.dialCode ?? '').trim();

  if (code === '' || name === '') {
    return null;
  }

  return {
    code,
    name,
    dialCode,
  };
};

export const COUNTRY_CATALOG: CountryCatalogEntry[] = (Array.isArray(rawCountryCatalog) ? rawCountryCatalog : [])
  .map((entry) => normalizeCountryCatalogEntry(entry as Partial<CountryCatalogEntry>))
  .filter((entry): entry is CountryCatalogEntry => entry !== null);

export const COUNTRIES = COUNTRY_CATALOG.map(({ code, name }) => ({ code, name }));

export const PHONE_DIAL_CODES = Object.fromEntries(
  COUNTRY_CATALOG.map(({ code, dialCode }) => [code, dialCode]),
) as Record<string, string>;

export const SORTED_COUNTRIES = [...COUNTRIES].sort((left, right) => left.name.localeCompare(right.name));

export const PRIORITY_COUNTRIES = ['MY', 'TH', 'SG', 'ID', 'BN', 'MM', 'VN'] as const;

export const PHONE_OPTIONS = SORTED_COUNTRIES.map((country) => ({
  country: country.code,
  countryName: country.name,
  dialCode: PHONE_DIAL_CODES[country.code] ?? '',
  flagClassName: `fi fi-${country.code.toLowerCase()}`,
}));

export const PRIORITY_PHONE_OPTIONS = PRIORITY_COUNTRIES
  .map((countryCode) => PHONE_OPTIONS.find((country) => country.country === countryCode))
  .filter((country): country is (typeof PHONE_OPTIONS)[number] => Boolean(country));

export const OTHER_PHONE_OPTIONS = PHONE_OPTIONS.filter(
  (country) => !PRIORITY_COUNTRIES.includes(country.country as (typeof PRIORITY_COUNTRIES)[number]),
);

export const PRIORITY_SORTED_COUNTRIES = PRIORITY_COUNTRIES
  .map((countryCode) => SORTED_COUNTRIES.find((country) => country.code === countryCode))
  .filter((country): country is (typeof SORTED_COUNTRIES)[number] => Boolean(country));

export const OTHER_SORTED_COUNTRIES = SORTED_COUNTRIES.filter(
  (country) => !PRIORITY_COUNTRIES.includes(country.code as (typeof PRIORITY_COUNTRIES)[number]),
);

export function findCountryByCode(code: string) {
  const normalizedCode = code.trim().toUpperCase();

  return COUNTRIES.find((country) => country.code === normalizedCode) ?? null;
}

export function humanizeCountry(code: string) {
  const normalizedCode = code.trim().toUpperCase();
  const country = findCountryByCode(normalizedCode);

  return country ? `${country.name} (${country.code})` : normalizedCode || '-';
}
