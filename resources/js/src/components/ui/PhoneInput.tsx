"use client";

import { forwardRef } from 'react';
import parsePhoneNumberFromString, { parseIncompletePhoneNumber } from 'libphonenumber-js';

import { CountryDropdown, CountryFlag, type CountryDropdownOption } from './CountryDropdown';

export type PhoneDropdownOption = CountryDropdownOption & {
  countryCode?: string;
  dialCode: string;
};

type PhoneInputProps = {
  codeId?: string;
  inputId?: string;
  codeOptions: PhoneDropdownOption[];
  codeValue?: string;
  numberValue?: string;
  onCodeChange?: (value: string, option: PhoneDropdownOption) => void;
  onNumberChange?: (value: string) => void;
  onBlur?: () => void;
  disabled?: boolean;
  codePlaceholder?: string;
  numberPlaceholder?: string;
  codeClassName?: string;
  inputClassName?: string;
};

const PhoneInput = forwardRef<HTMLInputElement, PhoneInputProps>(function PhoneInput(
  {
    codeId,
    inputId,
    codeOptions,
    codeValue,
    numberValue,
    onCodeChange,
    onNumberChange,
    onBlur,
    disabled = false,
    codePlaceholder = 'Code',
    numberPlaceholder = '123456789',
    codeClassName,
    inputClassName,
  },
  ref,
) {
  const handleChange = (rawValue: string) => {
    const parsedValue = parseIncompletePhoneNumber(rawValue);

    if (parsedValue === '') {
      onNumberChange?.('');
      return;
    }

    if (parsedValue.startsWith('+')) {
      const parsedPhoneNumber = parsePhoneNumberFromString(parsedValue);

      if (parsedPhoneNumber) {
        const detectedDialCode = `+${parsedPhoneNumber.countryCallingCode}`;
        const detectedOption = codeOptions.find(
          (option) =>
            option.dialCode === detectedDialCode &&
            option.countryCode === parsedPhoneNumber.country,
        ) ?? codeOptions.find((option) => option.dialCode === detectedDialCode);

        if (detectedOption) {
          onCodeChange?.(detectedOption.value, detectedOption);
        }

        onNumberChange?.(parsedPhoneNumber.nationalNumber.replace(/^0+/, ''));
        return;
      }
    }

    onNumberChange?.(parsedValue.replace(/\D/g, '').replace(/^0+/, ''));
  };

  return (
    <div className="grid grid-cols-1 gap-3 sm:grid-cols-[180px_minmax(0,1fr)]">
      <CountryDropdown
        id={codeId}
        options={codeOptions}
        value={codeValue}
        onChange={(value, option) => onCodeChange?.(value, option as PhoneDropdownOption)}
        placeholder={codePlaceholder}
        searchPlaceholder="Search country or dial code..."
        emptyMessage="No country code found."
        className={codeClassName}
        renderSelectedContent={(option) => (
          <span className="flex min-w-0 items-center gap-2.5 truncate">
            <CountryFlag flagCode={option.flagCode} emoji={option.emoji} className="h-4 w-[22px]" />
            <span className="truncate text-base font-medium">{(option as PhoneDropdownOption).dialCode}</span>
          </span>
        )}
      />
      <input
        ref={ref}
        id={inputId}
        type="tel"
        autoComplete="tel-national"
        inputMode="numeric"
        pattern="[0-9]*"
        placeholder={numberPlaceholder}
        value={numberValue ?? ''}
        disabled={disabled}
        onBlur={onBlur}
        onChange={(event) => handleChange(event.target.value)}
        className={inputClassName}
      />
    </div>
  );
});

export { PhoneInput };
