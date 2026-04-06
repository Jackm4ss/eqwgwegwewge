import { countries } from 'country-data-list';

type CountryDataListEntry = {
  alpha2?: string;
  alpha3?: string;
  countryCallingCodes?: string[];
  emoji?: string;
  name?: string;
  status?: string;
};

export type CountryCatalogEntry = {
  alpha3: string;
  code: string;
  dialCode: string;
  dialCodes: string[];
  emoji: string;
  name: string;
};

export type CountryOption = {
  alpha3: string;
  code: string;
  emoji: string;
  flagClassName: string;
  name: string;
};

export type PhoneOption = {
  country: string;
  countryName: string;
  dialCode: string;
  emoji: string;
  flagClassName: string;
};

export type SearchablePhoneOption = PhoneOption & {
  key: string;
  value: string;
};

const rawCountries = Array.isArray((countries as { all?: unknown[] }).all)
  ? ((countries as { all: CountryDataListEntry[] }).all)
  : [];

const normalizeDialCode = (value: string) => value.replace(/\s+/g, '').trim();

const uniqueValues = (values: string[]) => Array.from(new Set(values));

const normalizeCountryCatalogEntry = (entry: CountryDataListEntry): CountryCatalogEntry | null => {
  const code = String(entry.alpha2 ?? '').trim().toUpperCase();
  const alpha3 = String(entry.alpha3 ?? '').trim().toUpperCase();
  const name = String(entry.name ?? '').trim();
  const emoji = String(entry.emoji ?? '').trim();
  const dialCodes = uniqueValues(
    (Array.isArray(entry.countryCallingCodes) ? entry.countryCallingCodes : [])
      .map((dialCode) => normalizeDialCode(String(dialCode ?? '')))
      .filter((dialCode) => /^\+\d+$/.test(dialCode)),
  );
  const dialCode = dialCodes[0] ?? '';
  const status = String(entry.status ?? '').trim().toLowerCase();

  if (code === '' || alpha3 === '' || name === '' || status === 'deleted') {
    return null;
  }

  return {
    alpha3,
    code,
    dialCode,
    dialCodes,
    emoji,
    name,
  };
};

export const COUNTRY_CATALOG: CountryCatalogEntry[] = rawCountries
  .map((entry) => normalizeCountryCatalogEntry(entry))
  .filter((entry): entry is CountryCatalogEntry => entry !== null);

export const COUNTRIES: CountryOption[] = COUNTRY_CATALOG.map(({ alpha3, code, emoji, name }) => ({
  alpha3,
  code,
  emoji,
  flagClassName: `fi fi-${code.toLowerCase()}`,
  name,
}));

const COUNTRY_CATALOG_BY_CODE = new Map(COUNTRY_CATALOG.map((country) => [country.code, country]));

export const PHONE_DIAL_CODES = Object.fromEntries(
  COUNTRY_CATALOG.map(({ code, dialCode }) => [code, dialCode]),
) as Record<string, string>;

export const SORTED_COUNTRIES = [...COUNTRIES].sort((left, right) => left.name.localeCompare(right.name));

export const PRIORITY_COUNTRIES = ['MY', 'TH', 'SG', 'ID', 'BN', 'MM', 'VN'] as const;

export const PHONE_OPTIONS: PhoneOption[] = SORTED_COUNTRIES.map((country) => ({
  country: country.code,
  countryName: country.name,
  dialCode: PHONE_DIAL_CODES[country.code] ?? '',
  emoji: country.emoji,
  flagClassName: country.flagClassName,
})).filter((country) => country.dialCode !== '');

export const SEARCHABLE_PHONE_OPTIONS: SearchablePhoneOption[] = SORTED_COUNTRIES.flatMap((country) => {
  const countryEntry = COUNTRY_CATALOG_BY_CODE.get(country.code);
  const dialCodes = countryEntry?.dialCodes ?? [];

  return dialCodes.map((dialCode) => ({
    key: `${country.code}:${dialCode}`,
    value: `${country.code}:${dialCode}`,
    country: country.code,
    countryName: country.name,
    dialCode,
    emoji: country.emoji,
    flagClassName: country.flagClassName,
  }));
});

export const PRIORITY_PHONE_OPTIONS = PRIORITY_COUNTRIES
  .map((countryCode) => PHONE_OPTIONS.find((country) => country.country === countryCode))
  .filter((country): country is PhoneOption => Boolean(country));

export const OTHER_PHONE_OPTIONS = PHONE_OPTIONS.filter(
  (country) => !PRIORITY_COUNTRIES.includes(country.country as (typeof PRIORITY_COUNTRIES)[number]),
);

export const PRIORITY_SEARCHABLE_PHONE_OPTIONS = PRIORITY_COUNTRIES
  .flatMap((countryCode) => SEARCHABLE_PHONE_OPTIONS.filter((country) => country.country === countryCode));

export const OTHER_SEARCHABLE_PHONE_OPTIONS = SEARCHABLE_PHONE_OPTIONS.filter(
  (country) => !PRIORITY_COUNTRIES.includes(country.country as (typeof PRIORITY_COUNTRIES)[number]),
);

export const PRIORITY_SORTED_COUNTRIES = PRIORITY_COUNTRIES
  .map((countryCode) => SORTED_COUNTRIES.find((country) => country.code === countryCode))
  .filter((country): country is CountryOption => Boolean(country));

export const OTHER_SORTED_COUNTRIES = SORTED_COUNTRIES.filter(
  (country) => !PRIORITY_COUNTRIES.includes(country.code as (typeof PRIORITY_COUNTRIES)[number]),
);

export function findCountryByCode(code: string) {
  const normalizedCode = code.trim().toUpperCase();

  return COUNTRIES.find((country) => country.code === normalizedCode) ?? null;
}

export function findSearchablePhoneOptionByValue(value: string) {
  return SEARCHABLE_PHONE_OPTIONS.find((option) => option.value === value) ?? null;
}

export function findSearchablePhoneOptionByDialCode(dialCode: string, countryHint?: string) {
  const normalizedDialCode = normalizeDialCode(dialCode);
  const normalizedCountryHint = countryHint?.trim().toUpperCase() ?? '';
  const matchingOptions = SEARCHABLE_PHONE_OPTIONS.filter((option) => option.dialCode === normalizedDialCode);

  if (matchingOptions.length === 0) {
    return null;
  }

  return matchingOptions.find((option) => option.country === normalizedCountryHint) ?? matchingOptions[0];
}

export function findPrimarySearchablePhoneOption(countryCode: string) {
  const normalizedCountryCode = countryCode.trim().toUpperCase();

  return SEARCHABLE_PHONE_OPTIONS.find((option) => option.country === normalizedCountryCode) ?? null;
}

export function resolveSearchablePhoneOption(
  selectedValue: string,
  phoneCountryCode: string,
  countryHint?: string,
  fallbackCountryCode = 'MY',
) {
  const selectedOption = findSearchablePhoneOptionByValue(selectedValue);
  const normalizedPhoneCountryCode = normalizeDialCode(phoneCountryCode);

  if (selectedOption && selectedOption.dialCode === normalizedPhoneCountryCode) {
    return selectedOption;
  }

  return (
    findSearchablePhoneOptionByDialCode(phoneCountryCode, countryHint)
    ?? findPrimarySearchablePhoneOption(countryHint ?? '')
    ?? findPrimarySearchablePhoneOption(fallbackCountryCode)
    ?? null
  );
}

export function humanizeCountry(code: string) {
  const normalizedCode = code.trim().toUpperCase();
  const country = findCountryByCode(normalizedCode);

  return country ? `${country.name} (${country.code})` : normalizedCode || '-';
}
